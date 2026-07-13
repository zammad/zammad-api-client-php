<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when Zammad's rate limit is exceeded and all automatic retries fail.
 *
 * {@see \ZammadAPIClient\Core\RetryAfterMiddleware} intercepts HTTP 429
 * responses and retries the request automatically up to the configured
 * `$maxRetries` limit, honouring the `Retry-After` response header.
 * This exception is only raised when all retry attempts are exhausted
 * and the server still returns 429.
 *
 * The {@see self::$retryAfterSeconds} property reflects the last
 * `Retry-After` value received, giving the caller the opportunity to
 * implement custom back-off logic or queue the request for later execution.
 *
 * The HTTP status code (429) is set as the exception code.
 */
final class RateLimitException extends \RuntimeException implements ZammadException
{
    /**
     * @param string $message             Human-readable rate-limit message.
     * @param int    $retryAfterSeconds   Seconds to wait before retrying, from the last Retry-After header.
     */
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message, 429);
    }
}
