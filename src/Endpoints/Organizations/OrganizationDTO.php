<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Organizations;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
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
 * Server-assigned fields (`id`, `created_at`, `updated_at`) default to null and
 * are populated by the API after `create()` or `find()`.
 */
final readonly class OrganizationDTO implements DTOInterface
{
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public string $name,
        public ?string $note = null,
        public ?bool $active = null,
        public ?int $id = null,
        public ?DateTimeImmutable $created_at = null,
        public ?DateTimeImmutable $updated_at = null,
    ) {
    }
}
