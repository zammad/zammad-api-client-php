<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Transport;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 decorator that transparently retries on HTTP 429 (Too Many Requests).
 *
 * Zammad enforces rate limits and signals them via HTTP 429 with a
 * `Retry-After` header containing the number of seconds to wait. This
 * middleware intercepts the response before it reaches {@see RequestHandler},
 * sleeps for the requested duration, and resends the original request — up to
 * $maxRetries times. If all retries are exhausted the final 429 response is
 * returned so that `RequestHandler::dispatch()` can map it to a
 * {@see \ZammadAPIClient\Exceptions\RateLimitException}.
 *
 * Placing retry logic here (in the PSR-18 layer) rather than in
 * `RequestHandler` keeps the handler free of sleep/loop state and makes the
 * retry policy swappable by injecting a different decorator.
 *
 * Note: Uses {@see sleep()} which blocks the calling process. In asynchronous
 * contexts (e.g. ReactPHP, Amp) this will block the event loop. Replace this
 * middleware with an async-aware variant when running in such environments.
 */
final class RetryAfterMiddleware implements ClientInterface
{
    private const DEFAULT_RETRY_DELAY = 5;
    private const MAX_RETRY_DELAY     = 60;

    /**
     * @param ClientInterface $next         Inner PSR-18 client to delegate to (typically Guzzle).
     * @param int             $maxRetries   Maximum number of retry attempts after a 429.
     * @param int             $defaultDelay Seconds to wait when the Retry-After header is absent.
     */
    public function __construct(
        private ClientInterface $next,
        private int $maxRetries = 3,
        private int $defaultDelay = self::DEFAULT_RETRY_DELAY,
    ) {
    }

    /**
     * Sends the request, sleeping and retrying on HTTP 429 up to $maxRetries times.
     *
     * The body stream is rewound before each attempt to support non-seekable
     * PSR-18 clients (not every implementation rewinds automatically).
     * If $maxRetries is exhausted the last 429 response is returned as-is
     * for the caller to handle.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $attempt = 0;

        do {
            $request->getBody()->rewind();
            $response = $this->next->sendRequest($request);

            if ($response->getStatusCode() !== 429) {
                return $response;
            }

            $header = $response->getHeaderLine('Retry-After');
            $retryAfter = match (true) {
                is_numeric($header)                     => (int) $header,
                ($parsed = self::parseHttpDate($header)) !== null => $parsed,
                default                                 => $this->defaultDelay,
            };

            sleep(min($retryAfter, self::MAX_RETRY_DELAY));
            $attempt++;
        } while ($attempt < $this->maxRetries);

        return $response;
    }

    /**
     * Parses an HTTP-date header value per RFC 7231 and returns the
     * delay in seconds from now, or null if parsing fails.
     */
    private static function parseHttpDate(string $header): ?int
    {
        $timestamp = strtotime($header);

        return $timestamp !== false ? max(0, $timestamp - time()) : null;
    }
}
