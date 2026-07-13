<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketPriorities;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
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
 */
final readonly class TicketPriorityDTO implements DTOInterface
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
