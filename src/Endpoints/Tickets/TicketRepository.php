<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Tickets;

use ZammadAPIClient\Core\Repository\AbstractRepository;
use ZammadAPIClient\Core\Contracts\DeletableInterface;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleDTO;

/**
 * Repository for the `/api/v1/tickets` endpoint.
 *
 * Tickets are the central resource in Zammad, representing support requests,
 * incidents, tasks, or any communication thread. This repository provides
 * full CRUD access including deletion; ticket articles (replies, notes, emails)
 * are managed separately via {@see \ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository}.
 *
 * @extends AbstractRepository<TicketDTO>
 */
final class TicketRepository extends AbstractRepository implements DeletableInterface
{
    /**
     * Streams all articles belonging to the given ticket.
     *
     * Delegates to the `/ticket_articles/by_ticket/{id}` endpoint.
     *
     * @param int $ticketId Zammad ticket ID whose articles should be fetched.
     * @return \Generator<int, TicketArticleDTO>
     */
    public function getTicketArticles(int $ticketId): iterable
    {
        return $this->paginateWith(
            "ticket_articles/by_ticket/{$ticketId}",
            TicketArticleDTO::class,
            ['expand' => 'true'],
            'ticket_articles',
        );
    }

    public function delete(int $id): void
    {
        $this->handler->delete("{$this->resourcePath}/{$id}");
    }
}
