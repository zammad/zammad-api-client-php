<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Transport;

use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Exceptions\AuthenticationException;
use ZammadAPIClient\Exceptions\ForbiddenException;
use ZammadAPIClient\Exceptions\NetworkException;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Exceptions\RateLimitException;
use ZammadAPIClient\Exceptions\ServerErrorException;
use ZammadAPIClient\Exceptions\ValidationException;
use ZammadAPIClient\Exceptions\ZammadException;
use JsonException;

/**
 * Concrete PSR-18 based HTTP transport for the Zammad REST API.
 *
 * This class is the only place in the library that touches the network. It:
 *  - Prepends $baseUrl to every relative URI.
 *  - Serialises PHP arrays as JSON request bodies via PSR-17 stream factories.
 *  - Deserialises JSON responses to plain arrays.
 *  - Maps HTTP error codes to typed domain exceptions before returning
 *    (see {@see self::dispatch()} and the private `mapError`/`validationError` methods).
 *  - Stores the last PSR-7 response so callers can inspect headers afterward.
 *
 * HTTP 429 rate-limit retry is handled transparently — no manual wiring needed.
 */
final class RequestHandler implements RequestHandlerInterface
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private string $baseUrl;
    private LoggerInterface $logger;
    private ?ResponseInterface $lastResponse = null;

    /**
     * @param ClientInterface         $httpClient PSR-18 client (any implementation).
     * @param RequestFactoryInterface $factory    PSR-17 factory; must also implement {@see StreamFactoryInterface}.
     * @param string                  $baseUrl    Base URL incl. API prefix.
     * @param LoggerInterface         $logger     PSR-3 logger; defaults to NullLogger.
     * @param int                     $maxRetries Max retries on HTTP 429 (0 = disable).
     */
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $factory,
        string $baseUrl,
        LoggerInterface $logger = new NullLogger(),
        int $maxRetries = 3,
    ) {
        if (!$factory instanceof StreamFactoryInterface) {
            throw new InvalidArgumentException(
                'The factory must implement both RequestFactoryInterface and StreamFactoryInterface.',
            );
        }
        $this->httpClient = $maxRetries > 0
            ? new RetryAfterMiddleware($httpClient, maxRetries: $maxRetries)
            : $httpClient;
        $this->requestFactory = $factory;
        $this->streamFactory = $factory;
        $this->baseUrl = $baseUrl;
        $this->logger = $logger;
    }

    /**
     * Returns the raw PSR-7 response from the most recent request, or null if
     * no request has been made yet.
     *
     * Useful for reading response headers after a repository call.
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
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

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new NetworkException(
                'Failed to decode JSON response: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Performs a GET request and returns the unprocessed response body.
     *
     * Unlike {@see self::get()}, the body is NOT JSON-decoded. Use this for
     * binary endpoints such as ticket attachment downloads.
     *
     * @param array<string, mixed>  $query   URL query parameters to append.
     * @param array<string, string> $headers Additional HTTP headers to send.
     */
    public function getRaw(string $uri, array $query = [], array $headers = []): string
    {
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        $options = !empty($headers) ? ['headers' => $headers] : [];

        return (string) $this->dispatch('GET', $uri, $options)->getBody();
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
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
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
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
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

        try {
            $request = $this->requestFactory->createRequest($method, $fullUri);

            if (isset($options['headers']) && is_array($options['headers'])) {
                foreach ($options['headers'] as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }
            }

            if (isset($options['body']) && $options['body'] !== null) {
                $body = is_string($options['body'])
                    ? $options['body']
                    : json_encode($options['body'], JSON_THROW_ON_ERROR);
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
            $status === 403 => new ForbiddenException("Access denied: {$uri}"),
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
        $raw = (string) $response->getBody();
        $body = $this->decodeLenient($raw);

        $message = is_string($body['error'] ?? null)
            ? $body['error']
            : $this->extractValidationMessage($raw);

        return new ValidationException(
            $message,
            $this->extractValidationErrors($body),
        );
    }

    /**
     * @param array<string, mixed> $body
     * @return array<int|string, mixed>
     */
    private function extractValidationErrors(array $body): array
    {
        $details = $body['details'] ?? $body['error_details'] ?? null;

        return is_array($details) ? $details : [];
    }

    private function extractValidationMessage(string $raw): string
    {
        $trimmed = ltrim($raw);

        if (str_starts_with($trimmed, '<!') || str_starts_with($trimmed, '<html')) {
            return 'Validation failed (Zammad returned an error page, HTTP 422).';
        }

        return 'Validation failed — ' . substr($raw, 0, 200);
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
