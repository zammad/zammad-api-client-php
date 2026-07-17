<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketStates;

use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/ticket_states` endpoint.
 *
 * Ticket states represent the lifecycle stage of a ticket (e.g. "new", "open",
 * "closed", "pending reminder"). States are system-configured in Zammad and
 * rarely modified via the API, but this repository provides full CRUD access
 * for completeness (e.g. to create custom states or check available values before
 * assigning a state to a ticket).
 *
 * @extends AbstractRepository<TicketStateDTO>
 */
final class TicketStateRepository extends AbstractRepository
{
    /**
     * Returns 'ticket_states' — the JSON array key in Zammad's paginated state list response.
     */
    protected function getListKey(): string
    {
        return 'ticket_states';
    }
}
