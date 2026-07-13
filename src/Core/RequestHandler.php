<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Exceptions\AuthenticationException;
use ZammadAPIClient\Exceptions\NetworkException;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Exceptions\RateLimitException;
use ZammadAPIClient\Exceptions\ServerErrorException;
use ZammadAPIClient\Exceptions\ValidationException;
use ZammadAPIClient\Exceptions\ZammadException;

/**
 * Concrete PSR-18 based HTTP transport for the Zammad REST API.
 *
 * This class is the only place in the library that touches the network. It:
 *  - Prepends $baseUrl to every relative URI.
 *  - Attaches the `X-On-Behalf-Of` header when impersonation is active.
 *  - Serialises PHP arrays as JSON request bodies via PSR-17 stream factories.
 *  - Deserialises JSON responses to plain arrays.
 *  - Maps HTTP error codes to typed domain exceptions before returning
 *    (see {@see self::dispatch()} and the private `mapError`/`validationError` methods).
 *  - Stores the last PSR-7 response so callers can inspect headers afterward.
 *
 * Retry logic for HTTP 429 is not here; it lives in
 * {@see RetryAfterMiddleware}, which wraps the PSR-18 client before injection.
 * This separation keeps each class to a single responsibility.
 */
final class RequestHandler implements RequestHandlerInterface
{
    private ?ResponseInterface $lastResponse = null;
    private ?int $onBehalfOfUser = null;

    /**
     * @param ClientInterface         $httpClient     PSR-18 client (typically Guzzle + {@see RetryAfterMiddleware}).
     * @param RequestFactoryInterface $requestFactory PSR-17 factory for building PSR-7 requests.
     * @param StreamFactoryInterface  $streamFactory  PSR-17 factory for JSON body streams.
     * @param string                  $baseUrl        Base URL incl. API prefix (`https://zammad.example.com/api/v1`).
     * @param LoggerInterface         $logger         PSR-3 logger; defaults to a no-op NullLogger.
     */
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUrl,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Returns the raw PSR-7 response from the most recent request, or null if
     * no request has been made yet.
     *
     * Useful for reading response headers (e.g. `X-Total-Count`) after a
     * repository call.
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Activates or deactivates API-level user impersonation.
     *
     * When $userId is non-null it is forwarded as the `X-On-Behalf-Of` HTTP
     * header on every subsequent request, causing Zammad to execute actions
     * as the given agent. Pass null to disable impersonation.
     *
     * @see https://docs.zammad.org/en/latest/api/intro.html#x-on-behalf-of
     */
    public function setOnBehalfOfUser(?int $userId): void
    {
        $this->onBehalfOfUser = $userId;
    }

    /**
     * Dispatches an HTTP request and returns the JSON-decoded body as an array.
     *
     * Error mapping happens inside {@see self::dispatch()} before JSON decoding,
     * so callers never receive a response with a 4xx/5xx body — an exception is
     * thrown instead. An empty response body is treated as an empty array (some
     * Zammad DELETE endpoints return 200 with no body).
     *
     * @param array<string, mixed> $options Raw PSR-18 options forwarded verbatim to the HTTP client.
     * @return array<string, mixed>
     */
    public function request(
        string $method,
        string $uri,
        array $options = [],
    ): array {
        $response = $this->dispatch($method, $uri, $options);
        $body = (string) $response->getBody();

        if ($body === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Performs a GET request and returns the unprocessed response body.
     *
     * Unlike {@see self::get()}, the body is NOT JSON-decoded. Use this for
     * binary endpoints such as ticket attachment downloads.
     *
     * @param array<string, mixed> $query URL query parameters to append.
     */
    public function getRaw(string $uri, array $query = []): string
    {
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return (string) $this->dispatch('GET', $uri, [])->getBody();
    }

    /**
     * Performs a GET request and returns the decoded JSON body.
     *
     * Query parameters are serialised with {@see http_build_query()} and
     * appended to the URI; they are not sent as a request body.
     *
     * @param array<string, mixed> $query URL query parameters.
     * @return array<string, mixed>
     */
    public function get(string $uri, array $query = []): array
    {
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $this->request('GET', $uri);
    }

    /**
     * Performs a POST request with a JSON body and returns the decoded response.
     *
     * $body is serialised to JSON and sent with `Content-Type: application/json`.
     * Use this for resource creation (Zammad convention: POST → 201 or 200 with body).
     *
     * @param array<string, mixed> $body Payload fields; serialised to JSON automatically.
     * @return array<string, mixed>
     */
    public function post(string $uri, array $body = []): array
    {
        return $this->request('POST', $uri, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body),
        ]);
    }

    /**
     * Performs a PUT request with a JSON body and returns the decoded response.
     *
     * Used for both full resource replacements (update) and partial updates
     * (patch), since Zammad uses PUT for both semantics.
     *
     * @param array<string, mixed> $body Payload fields; serialised to JSON automatically.
     * @return array<string, mixed>
     */
    public function put(string $uri, array $body = []): array
    {
        return $this->request('PUT', $uri, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body),
        ]);
    }

    /**
     * Performs a DELETE request and returns the decoded JSON body.
     *
     * Zammad's API returns the deleted resource's state in the response body,
     * which can be used as a confirmation receipt. An empty body returns `[]`.
     *
     * @return array<string, mixed>
     */
    public function delete(string $uri): array
    {
        return $this->request('DELETE', $uri);
    }

    /**
     * Performs the HTTP request and maps error status codes to typed
     * exceptions - before the body is interpreted as JSON.
     *
     * @param array<string, mixed> $options
     */
    private function dispatch(string $method, string $uri, array $options): ResponseInterface
    {
        $fullUri = $this->baseUrl . '/' . ltrim($uri, '/');
        $this->logger->debug("Zammad API request: {$method} {$fullUri}");

        if ($this->onBehalfOfUser !== null) {
            $headers = $options['headers'] ?? [];
            $options['headers'] = is_array($headers) ? $headers : [];
            $options['headers']['X-On-Behalf-Of'] = (string) $this->onBehalfOfUser;
        }

        try {
            $request = $this->requestFactory->createRequest($method, $fullUri);

            if (isset($options['headers']) && is_array($options['headers'])) {
                foreach ($options['headers'] as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }
            }

            if (isset($options['body']) && $options['body'] !== null) {
                $body = is_string($options['body']) ? $options['body'] : json_encode($options['body']);
                $request = $request->withBody($this->streamFactory->createStream((string) $body));
            }

            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException($e->getMessage(), previous: $e);
        }

        $this->lastResponse = $response;
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return $response;
        }

        throw $this->mapError($status, $uri, $response);
    }

    private function mapError(int $status, string $uri, ResponseInterface $response): ZammadException
    {
        return match (true) {
            $status === 401 => new AuthenticationException('Invalid credentials'),
            $status === 404 => new NotFoundException("Resource not found: {$uri}"),
            $status === 422 => $this->validationError($response),
            $status === 429 => new RateLimitException(
                'Too many requests',
                (int) ($response->getHeaderLine('Retry-After') ?: 60),
            ),
            $status >= 500 => new ServerErrorException("Server error: {$status}"),
            default => new NetworkException("Unexpected status: {$status}"),
        };
    }

    private function validationError(ResponseInterface $response): ValidationException
    {
        $body = $this->decodeLenient((string) $response->getBody());

        return new ValidationException(
            is_string($body['error'] ?? null) ? $body['error'] : 'Validation failed',
            is_array($body['details'] ?? null) ? $body['details'] : [],
        );
    }

    /** @return array<string, mixed> */
    private function decodeLenient(string $body): array
    {
        if ($body === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
