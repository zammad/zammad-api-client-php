<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Tickets;

use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/tickets` endpoint.
 *
 * Tickets are the central resource in Zammad, representing support requests,
 * incidents, tasks, or any communication thread. This repository provides
 * full CRUD access; ticket articles (replies, notes, emails) are managed
 * separately via {@see \ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository}.
 *
 * @extends AbstractRepository<TicketDTO>
 */
final class TicketRepository extends AbstractRepository
{
    /**
     * Returns 'tickets' — the JSON array key in Zammad's paginated ticket list response.
     *
     * Zammad wraps results in `{"tickets": [...], "assets": {...}}`; this key
     * tells {@see \ZammadAPIClient\Core\AbstractRepository::extractItems()} where to find the items.
     */
    protected function getListKey(): string
    {
        return 'tickets';
    }
}
