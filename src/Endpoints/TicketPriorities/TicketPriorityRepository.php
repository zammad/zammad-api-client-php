<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketPriorities;

use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/ticket_priorities` endpoint.
 *
 * Ticket priorities classify the urgency of a ticket (e.g. "low", "normal", "high").
 * Like states, priorities are system-configured in Zammad. This repository
 * provides full CRUD access for retrieving available priorities (to populate
 * dropdowns) and for managing custom priority levels.
 *
 * @extends AbstractRepository<TicketPriorityDTO>
 */
final class TicketPriorityRepository extends AbstractRepository
{
    /**
     * Returns 'ticket_priorities' — the JSON array key in Zammad's paginated priority list response.
     */
    protected function getListKey(): string
    {
        return 'ticket_priorities';
    }
}
