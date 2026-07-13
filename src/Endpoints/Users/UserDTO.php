<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Users;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad user resource (`/api/v1/users`).
 *
 * Users encompass both agents (Zammad staff) and customers (end-users). The
 * distinction is made by the `role_id` field: agents have the "Agent" role,
 * customers have the "Customer" role.
 *
 * Key fields:
 *  - `login`           — Unique username; required for agents, optional for customers.
 *  - `email`           — Primary email address; used for notifications and authentication.
 *  - `firstname`/`lastname` — Display name components.
 *  - `organization_id` — Links the user to an organization (customers only, typically).
 *  - `role_id`         — Determines whether the user is an agent or a customer.
 *
 * All fields are nullable because the minimum required fields for creation
 * differ between agents (need `login`) and customers (need `email`), and
 * partial construction is common when building a DTO just to update one field.
 */
final readonly class UserDTO implements DTOInterface
{
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public ?string $login = null,
        public ?string $email = null,
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $phone = null,
        public ?int $organization_id = null,
        public ?int $role_id = null,
        public ?bool $active = null,
        public ?int $id = null,
        public ?DateTimeImmutable $created_at = null,
        public ?DateTimeImmutable $updated_at = null,
    ) {
    }
}
