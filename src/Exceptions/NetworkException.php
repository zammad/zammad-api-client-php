<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when a transport-level error prevents the request from completing.
 *
 * Unlike HTTP error status codes (which are caught and mapped to typed
 * exceptions in {@see \ZammadAPIClient\Core\Transport\RequestHandler::dispatch()}),
 * this exception covers failures that occur before or after an HTTP response
 * is received:
 *  - DNS resolution failure.
 *  - TLS handshake error or certificate verification failure.
 *  - Connection refused or timed out.
 *  - Unexpected HTTP status codes that do not map to a specific exception
 *    (i.e. 3xx without redirect following, or unrecognised 4xx).
 *
 * The original PSR-18 `ClientExceptionInterface` is preserved as the
 * `$previous` exception and can be accessed via {@see self::getPrevious()}.
 */
final class NetworkException extends \RuntimeException implements ZammadException
{
}
