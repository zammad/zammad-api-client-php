<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Marker interface for all exceptions thrown by the Zammad API client.
 *
 * Catching `ZammadException` allows callers to handle any client-specific
 * error without knowing the concrete subtype. All concrete exceptions extend
 * `RuntimeException` and implement this interface, so they can be caught as
 * either `\RuntimeException` or `ZammadException`.
 *
 * Exception hierarchy:
 *  - {@see AuthenticationException} — HTTP 401: invalid token or insufficient permissions.
 *  - {@see NotFoundException}       — HTTP 404: the requested resource does not exist.
 *  - {@see ValidationException}     — HTTP 422: the request payload was rejected by the API.
 *  - {@see RateLimitException}      — HTTP 429: rate limit reached (after all retries exhausted).
 *  - {@see ServerErrorException}    — HTTP 5xx: unexpected error on the Zammad server side.
 *  - {@see NetworkException}        — transport-level failure (DNS, TLS, connection refused, etc.).
 */
interface ZammadException extends \Throwable
{
}
