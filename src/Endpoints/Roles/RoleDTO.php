<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Roles;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad role resource (`/api/v1/roles`).
 *
 * A role is a named bundle of permissions. The default Zammad installation
 * ships with "Admin", "Agent" and "Customer"; administrators can add more.
 *
 * The `name` field is the display label; Zammad uses the numeric `id` when
 * assigning roles to a user via the `role_ids` field on
 * {@see \ZammadAPIClient\Endpoints\Users\UserDTO}.
 *
 * `permission_ids` and `group_ids` are writable on create and update: Zammad
 * applies them as associations, so a role can be created with its permissions
 * in a single request.
 *
 * Note that a customer token sees a reduced view of a role — Zammad replaces
 * `name` with the placeholder `"Role_<id>"` and omits `note` and
 * `default_at_signup`. Resolving a role by name therefore requires an agent or
 * admin token.
 *
 * Timestamp fields (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 */
final class RoleDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    /**
     * @param array<int>|null $permission_ids IDs of the permissions this role grants.
     * @param array<int|string, string|array<string>>|null $group_ids Map of group ID to
     *        access level (e.g. `[1 => 'full', 42 => ['read', 'change']]`). Only
     *        meaningful for roles that carry the `ticket.agent` permission —
     *        Zammad clears it for all others.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $note = null,
        public readonly ?bool $active = null,
        public readonly ?bool $default_at_signup = null,
        public readonly ?array $permission_ids = null,
        public readonly ?array $group_ids = null,
        public readonly ?int $id = null,
    ) {
    }
}
