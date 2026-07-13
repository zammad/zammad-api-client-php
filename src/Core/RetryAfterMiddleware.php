<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

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
 */
final class RetryAfterMiddleware implements ClientInterface
{
    /**
     * @param ClientInterface $next         Inner PSR-18 client to delegate to (typically Guzzle).
     * @param int             $maxRetries   Maximum number of retry attempts after a 429.
     * @param int             $defaultDelay Seconds to wait when the Retry-After header is absent.
     */
    public function __construct(
        private ClientInterface $next,
        private int $maxRetries = 3,
        private int $defaultDelay = 5,
    ) {
    }

    /**
     * Sends the request, sleeping and retrying on HTTP 429 up to $maxRetries times.
     *
     * The original PSR-7 request is replayed verbatim on each retry;
     * no body re-streaming is needed because PSR-7 streams are seekable by
     * default in Guzzle. If $maxRetries is exhausted the last 429 response
     * is returned as-is for the caller to handle.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $attempt = 0;

        do {
            $response = $this->next->sendRequest($request);

            if ($response->getStatusCode() !== 429) {
                return $response;
            }

            $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: $this->defaultDelay);
            sleep($retryAfter);
            $attempt++;
        } while ($attempt < $this->maxRetries);

        return $response;
    }
}
