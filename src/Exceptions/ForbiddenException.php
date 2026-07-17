<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when the Zammad API returns HTTP 403 Forbidden.
 *
 * Indicates that the authenticated user does not have permission to
 * perform the requested action. This differs from 401 AuthenticationException
 * (missing/invalid credentials) in that the credentials are valid but the
 * user lacks the necessary role or permissions.
 *
 * Common causes:
 *  - User is a customer without agent permissions.
 *  - API token lacks the required scope.
 *  - User's role does not grant access to the requested endpoint.
 */
final class ForbiddenException extends \RuntimeException implements ZammadException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 403);
    }
}
