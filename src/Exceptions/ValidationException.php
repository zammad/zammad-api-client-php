<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when the Zammad API returns HTTP 422 Unprocessable Entity.
 *
 * Indicates that the request payload was syntactically valid (parseable JSON)
 * but failed Zammad's business-logic validation. The response body carries a
 * human-readable `error` string and an optional `details` map; both are
 * preserved for the caller to inspect or display.
 *
 * Common causes:
 *  - A required field is missing (e.g. no `title` on a ticket).
 *  - A field value violates a constraint (e.g. unknown state name).
 *  - A referenced relation does not exist (e.g. non-existent group ID).
 *
 * The HTTP status code (422) is set as the exception code.
 */
final class ValidationException extends \RuntimeException implements ZammadException
{
    /**
     * @param string                   $message Human-readable error summary from the `error` field.
     * @param array<int|string, mixed> $errors  Detailed per-field validation messages from `details`.
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message, 422);
    }
}
