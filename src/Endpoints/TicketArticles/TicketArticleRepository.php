<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketArticles;

use ZammadAPIClient\Core\Repository\AbstractRepository;

/**
 * Repository for the `/api/v1/ticket_articles` endpoint.
 *
 * Ticket articles are the individual messages, notes, emails, and phone entries
 * that make up a ticket's communication thread. They are always linked to a
 * parent ticket; the standard `all()` / `search()` methods work across all
 * articles, while {@see self::getForTicket()} fetches only those belonging to
 * a specific ticket.
 *
 * Attachments within an article can be downloaded as raw bytes via
 * {@see self::getAttachmentContent()}.
 *
 * @extends AbstractRepository<TicketArticleDTO>
 */
final class TicketArticleRepository extends AbstractRepository
{
    /**
     * Streams all articles belonging to the given ticket, including relation data.
     *
     * Uses the dedicated `/ticket_articles/by_ticket/{ticketId}` endpoint rather
     * than the generic `all()` path because Zammad does not support filtering
     * the generic article list by ticket ID in a paginated way. The `expand=true`
     * parameter is appended so that author names and other relations are inlined.
     *
     * @param int $ticketId Zammad ticket ID whose articles should be fetched.
     * @return \Generator<int, TicketArticleDTO>
     */
    public function getForTicket(int $ticketId): iterable
    {
        return $this->paginateWith(
            "ticket_articles/by_ticket/{$ticketId}",
            TicketArticleDTO::class,
            ['expand' => 'true'],
            $this->getListKey(),
        );
    }

    /**
     * Downloads the raw binary content of a ticket attachment.
     *
     * Attachment content is served by the dedicated `/ticket_attachment/{ticket}/{article}/{attachment}`
     * endpoint. The response is binary (e.g. PDF, image); this method returns it as a raw
     * string without JSON decoding. Store or stream the result directly.
     *
     * All three IDs are required because Zammad uses them for permission checks:
     * the article must belong to the ticket, and the attachment to the article.
     *
     * @param int $ticketId     ID of the parent ticket.
     * @param int $articleId    ID of the article that contains the attachment.
     * @param int $attachmentId ID of the specific attachment (from the article's `attachments` array).
     * @return string           Raw binary content of the attachment.
     */
    public function getAttachmentContent(
        int $ticketId,
        int $articleId,
        int $attachmentId,
    ): string {
        $uri = "ticket_attachment/{$ticketId}/{$articleId}/{$attachmentId}";

        return $this->handler->getRaw($uri);
    }
}
