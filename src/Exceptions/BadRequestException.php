<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when the Zammad API returns HTTP 400 Bad Request.
 *
 * Indicates that the request was malformed in a way Zammad could not process
 * (as opposed to a 422 ValidationException, where the payload is syntactically
 * valid but fails business-logic validation).
 *
 * Common causes:
 *  - Missing or malformed query/body parameters.
 *  - Invalid filter expressions.
 *  - Endpoint-specific input that fails pre-validation parsing.
 */
final class BadRequestException extends \RuntimeException implements ZammadException
{
    /**
     * Creates an HTTP 400 exception with the API-provided message.
     */
    public function __construct(string $message = 'Bad request')
    {
        parent::__construct($message, 400);
    }
}
