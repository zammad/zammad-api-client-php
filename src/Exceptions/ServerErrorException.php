<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when the Zammad API returns an HTTP 5xx status code.
 *
 * A 5xx response means the server received the request but encountered an
 * unexpected internal error processing it. The client request itself was
 * technically valid; the problem is on the server side.
 *
 * Common causes:
 *  - Zammad application bug or unhandled exception.
 *  - Underlying database or background-job failure.
 *  - Zammad server is in maintenance mode or being restarted.
 *
 * The HTTP status code is included in the exception message (e.g. "Server error: 503").
 * No automatic retry is performed for 5xx errors — only HTTP 429 is retried
 * by {@see \ZammadAPIClient\Core\RetryAfterMiddleware}.
 */
final class ServerErrorException extends \RuntimeException implements ZammadException
{
}
