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
 *  - Forward the `X-On-Behalf-Of` header when impersonation is active.
 *  - Provide a raw byte-level escape hatch for binary responses ({@see self::getRaw()}).
 *
 * Implementations must not perform retry logic; that concern belongs to the
 * PSR-18 middleware stack (see {@see \ZammadAPIClient\Core\RetryAfterMiddleware}).
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
     * @param array<string, mixed> $query URL query parameters.
     * @throws \ZammadAPIClient\Exceptions\NetworkException On transport failure.
     */
    public function getRaw(string $uri, array $query = []): string;

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
     * Useful for inspecting response headers (e.g. `X-Total-Count` for
     * pagination) after a repository call.
     */
    public function getLastResponse(): ?ResponseInterface;

    /**
     * Sets or clears the user ID for API impersonation.
     *
     * When non-null the value is forwarded as the `X-On-Behalf-Of` HTTP header,
     * causing Zammad to execute the request as the given user. Pass null to
     * remove impersonation.
     *
     * @see https://docs.zammad.org/en/latest/api/intro.html#x-on-behalf-of
     */
    public function setOnBehalfOfUser(?int $userId): void;
}
