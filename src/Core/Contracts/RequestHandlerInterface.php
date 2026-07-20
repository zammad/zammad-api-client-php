<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Thin wrapper around the PSR-18 HTTP client for Zammad API calls.
 *
 * Responsibilities:
 *  - Serialise PHP arrays to JSON request bodies and deserialise responses.
 *  - Map HTTP error status codes to domain exceptions (4xx → client errors,
 *    5xx → {@see \ZammadAPIClient\Exceptions\ServerErrorException}).
 *  - Forward the `From` header when impersonation is active.
 *  - Provide a raw byte-level escape hatch for binary responses ({@see self::getRaw()}).
 *
 * Implementations may handle rate-limit retry internally; callers do not need
 * to configure it separately.
 */
interface RequestHandlerInterface
{
    /**
     * Executes an arbitrary HTTP request and returns the decoded JSON body.
     *
     * This is the central dispatch method; all convenience wrappers (get, post,
     * put, delete) delegate here. $options is passed verbatim to the underlying
     * PSR-18 client, which allows setting custom headers or timeouts per call.
     *
     * @param array<string, mixed> $options  PSR-18 client options (e.g. `['json' => [...]]`).
     * @return array<string, mixed>          JSON-decoded response body.
     * @throws \ZammadAPIClient\Exceptions\AuthenticationException For 401 responses.
     * @throws \ZammadAPIClient\Exceptions\NotFoundException       For 404 responses.
     * @throws \ZammadAPIClient\Exceptions\ValidationException     For 422 responses.
     * @throws \ZammadAPIClient\Exceptions\RateLimitException      For 429 responses (after retries).
     * @throws \ZammadAPIClient\Exceptions\ServerErrorException    For 5xx responses.
     * @throws \ZammadAPIClient\Exceptions\NetworkException        On transport-level failures.
     */
    public function request(
        string $method,
        string $uri,
        array $options = [],
    ): array;

    /**
     * Performs a GET request and returns the raw response body as a string.
     *
     * Use this instead of {@see self::get()} when the endpoint returns binary
     * data (e.g. ticket attachment content) that cannot be JSON-decoded.
     *
     * @param array<string, mixed> $query   URL query parameters.
     * @param array<string, string> $headers Additional HTTP headers to send.
     * @throws \ZammadAPIClient\Exceptions\NetworkException On transport failure.
     */
    public function getRaw(string $uri, array $query = [], array $headers = []): string;

    /**
     * Performs a GET request and returns the decoded JSON body.
     *
     * @param array<string, mixed> $query URL query parameters appended to the URI.
     * @return array<string, mixed>
     */
    public function get(string $uri, array $query = []): array;

    /**
     * Performs a POST request with a JSON body and returns the decoded response.
     *
     * @param array<string, mixed> $body Request payload; serialised to JSON automatically.
     * @return array<string, mixed>
     */
    public function post(string $uri, array $body = []): array;

    /**
     * Performs a PUT request with a JSON body and returns the decoded response.
     *
     * @param array<string, mixed> $body Request payload; serialised to JSON automatically.
     * @return array<string, mixed>
     */
    public function put(string $uri, array $body = []): array;

    /**
     * Performs a DELETE request and returns the decoded JSON body.
     *
     * Zammad's DELETE endpoints return the deleted resource's data, which
     * callers may use to confirm the operation.
     *
     * @return array<string, mixed>
     */
    public function delete(string $uri): array;

    /**
     * Returns the raw PSR-7 response of the most recent request, or null.
     *
     * Useful for inspecting response headers after a repository call.
     */
    public function getLastResponse(): ?ResponseInterface;
}
