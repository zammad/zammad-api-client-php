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
 * Timestamp fields (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 */
final class RoleDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public readonly string $name,
        public readonly ?string $note = null,
        public readonly ?bool $active = null,
        public readonly ?int $id = null,
    ) {
    }
}
