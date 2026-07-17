<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Users;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad user resource (`/api/v1/users`).
 *
 * Users encompass both agents (Zammad staff) and customers (end-users). The
 * distinction is made by the `role_ids` field: agents have the "Agent" role,
 * customers have the "Customer" role.
 *
 * Key fields:
 *  - `login`            — Unique username; required for agents, optional for customers.
 *  - `email`            — Primary email address; used for notifications and authentication.
 *  - `firstname`/`lastname` — Display name components.
 *  - `organization_id`  — Primary organization ID.
 *  - `organization_ids` — Array of secondary organization IDs.
 *  - `role_ids`         — Array of role IDs determining permissions.
 *
 * All fields are nullable because the minimum required fields for creation
 * differ between agents (need `login`) and customers (need `email`), and
 * partial construction is common when building a DTO just to update one field.
 */
final class UserDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    /**
     * @param array<int>|null      $organization_ids
     * @param array<int>|null      $role_ids
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public readonly ?string $login = null,
        public readonly ?string $email = null,
        public readonly ?string $firstname = null,
        public readonly ?string $lastname = null,
        public readonly ?string $phone = null,
        public readonly ?int $organization_id = null,
        public readonly ?array $organization_ids = null,
        public readonly ?array $role_ids = null,
        public readonly ?bool $active = null,
        public readonly ?int $id = null,
        public readonly ?DateTimeImmutable $created_at = null,
        public readonly ?DateTimeImmutable $updated_at = null,
        public readonly array $customFields = [],
    ) {
    }
}
