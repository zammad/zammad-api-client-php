<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Tickets;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Repository\DtoHydrator;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad ticket resource (`/api/v1/tickets`).
 *
 * Tickets are the central work item in Zammad. Each ticket belongs to a group,
 * has a state and priority, and is linked to a customer (and optionally an owner
 * agent and organization).
 *
 * Key fields:
 *  - `title`           — Subject line of the ticket (required on creation).
 *  - `group_id`        — The group responsible for the ticket (required on creation).
 *  - `state_id`        — References {@see \ZammadAPIClient\Endpoints\TicketStates\TicketStateDTO}.
 *  - `priority_id`     — References {@see \ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityDTO}.
 *  - `owner_id`        — Agent assigned to the ticket (0 or null = unassigned).
 *  - `customer_id`     — The end-user who submitted the ticket (required on creation).
 *  - `organization_id` — Derived from the customer's organization.
 *  - `number`          — Human-readable ticket number assigned by Zammad (read-only).
 *  - `pending_time`    — ISO 8601 datetime for pending states (pending reminder / pending close).
 *  - `article`         — Nested article payload for ticket creation
 *                        (array with keys: subject, body, type, internal, etc.).
 *
 * Hydration notes:
 *  - Missing fields resolve to null — no exception on incomplete API responses.
 *  - Extra/unknown fields are silently ignored (forward compatibility).
 *  - `owner_id` falls back to `assigned_to_id` because some older Zammad API
 *    versions used the latter name. The fallback ensures backward compatibility
 *    without requiring the caller to normalise the field name.
 *
 * Because of the `owner_id` fallback, this DTO overrides `fromArray()` instead
 * of relying on the generic {@see \ZammadAPIClient\Core\Traits\HydratesFromArray} trait.
 *
 * Timestamp fields (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 */
final class TicketDTO implements DTOInterface
{
    use HasTimestamps;
    use SerializesToArray;

    /**
     * @param array<string, mixed>|null $article Nested article payload for ticket creation
     * @param array<string, mixed>       $customFields
     */
    public function __construct(
        public readonly string $title,
        public readonly ?int $group_id = null,
        public readonly ?int $priority_id = null,
        public readonly ?int $state_id = null,
        public readonly ?int $organization_id = null,
        public readonly ?int $customer_id = null,
        public readonly ?int $owner_id = null,
        public readonly ?string $number = null,
        public readonly ?int $id = null,
        public readonly ?DateTimeImmutable $pending_time = null,
        public readonly ?array $article = null,
        public readonly array $customFields = [],
    ) {
    }

    public static function fromArray(array $data): static
    {
        if (!array_key_exists('owner_id', $data) && array_key_exists('assigned_to_id', $data)) {
            $data['owner_id'] = $data['assigned_to_id'];
        }

        return DtoHydrator::hydrate(static::class, $data);
    }
}
