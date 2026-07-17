<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Groups;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad group resource (`/api/v1/groups`).
 *
 * Groups are routing containers for tickets. Every ticket belongs to exactly one
 * group, which determines which agents can see and work on it. Groups also control
 * SLA assignments, notification rules, and signature selection.
 *
 * Server-assigned fields (`id`, `created_at`, `updated_at`) default to null so
 * the DTO can be constructed before persisting. After a `create()` or `find()` call
 * the returned DTO will have these fields populated by the server.
 */
final class GroupDTO implements DTOInterface
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
        public readonly ?DateTimeImmutable $created_at = null,
        public readonly ?DateTimeImmutable $updated_at = null,
        public readonly array $customFields = [],
    ) {
    }
}
