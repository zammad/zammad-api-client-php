<?php

declare(strict_types=1);

namespace ZammadAPIClient\Exceptions;

/**
 * Thrown when the Zammad API returns HTTP 404 Not Found.
 *
 * Indicates that no resource with the requested ID exists in Zammad.
 * Possible causes:
 *  - The resource was deleted (tickets/users can be deleted via the API or UI).
 *  - The ID is correct but the authenticated user lacks visibility due to group
 *    or permission restrictions (Zammad returns 404 for permission-hidden resources
 *    to avoid information leakage).
 *  - A typo in the resource path or ID.
 *
 * The exception message includes the URI of the failing request for debugging.
 */
final class NotFoundException extends \RuntimeException implements ZammadException
{
}
