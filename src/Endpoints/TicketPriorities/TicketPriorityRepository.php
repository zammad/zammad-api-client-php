<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketPriorities;

use ZammadAPIClient\Core\Repository\AbstractRepository;

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
}
