<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketStates;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad ticket state resource (`/api/v1/ticket_states`).
 *
 * Ticket states describe the current lifecycle stage of a ticket (e.g. "new",
 * "open", "pending reminder", "closed"). Each state belongs to a state type
 * (via `state_type_id`) that controls Zammad's internal behaviour, such as
 * whether a ticket counts as open or closed for SLA calculations.
 *
 * Key fields:
 *  - `name`          — Display label (e.g. `'open'`).
 *  - `state_type_id` — References the internal state type; determines Zammad's
 *                      automation behaviour for this state.
 *
 * Typically retrieved with `all()` to populate a state selector dropdown;
 * rarely created or modified via the API.
 */
final class TicketStateDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public readonly string $name,
        public readonly ?int $state_type_id = null,
        public readonly ?string $note = null,
        public readonly ?bool $active = null,
        public readonly ?int $id = null,
        public readonly ?DateTimeImmutable $created_at = null,
        public readonly ?DateTimeImmutable $updated_at = null,
    ) {
    }
}
