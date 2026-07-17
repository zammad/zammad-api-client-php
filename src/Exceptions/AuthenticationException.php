<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when the Zammad API returns HTTP 401 Unauthorized.
 *
 * Common causes:
 *  - The API token is invalid, expired, or has been revoked.
 *  - The token was created for a different Zammad instance.
 *  - The `From` header value does not exist or the token owner lacks
 *    impersonation rights.
 *
 * Resolution: verify the token in the Zammad admin panel under
 * *Avatar → Profile → Token Access* and ensure the required permissions are
 * granted.
 */
final class AuthenticationException extends \RuntimeException implements ZammadException
{
}
