<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketPriorities;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad ticket priority resource (`/api/v1/ticket_priorities`).
 *
 * Ticket priorities classify the urgency level of a ticket (e.g. "1 low",
 * "2 normal", "3 high"). The default Zammad installation ships with three
 * priorities; administrators can create additional ones via the API or UI.
 *
 * The `name` field is the display label; Zammad uses the numeric `id` when
 * assigning a priority to a ticket via the `priority_id` field on
 * {@see \ZammadAPIClient\Endpoints\Tickets\TicketDTO}.
 *
 * Timestamp fields (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 */
final class TicketPriorityDTO implements DTOInterface
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
