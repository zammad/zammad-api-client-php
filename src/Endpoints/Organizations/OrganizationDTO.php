<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Organizations;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad organization resource (`/api/v1/organizations`).
 *
 * Organizations group customer users under a shared company entity. A customer
 * can belong to one primary organization; agents and organizations are separate
 * (agents typically have no organization). Tickets created by a customer inherit
 * the customer's organization, enabling company-wide ticket views.
 *
 * Server-assigned fields (`id`) default to null. Timestamp fields
 * (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 */
final class OrganizationDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    /**
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $note = null,
        public readonly ?bool $active = null,
        public readonly ?int $id = null,
        public readonly array $customFields = [],
    ) {
    }
}
