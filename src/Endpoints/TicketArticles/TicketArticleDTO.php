<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketArticles;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad ticket article resource (`/api/v1/ticket_articles`).
 *
 * A ticket article is a single message within a ticket's communication thread.
 * It can represent an inbound email, an outbound reply, an internal note,
 * a phone call log, or any other communication channel Zammad supports.
 *
 * Key fields:
 *  - `ticket_id`    — The parent ticket this article belongs to.
 *  - `type`         — Channel/type string (e.g. `'email'`, `'note'`, `'phone'`).
 *  - `body`         — The content of the message.
 *  - `content_type` — MIME type of the body (`'text/plain'` or `'text/html'`).
 *
 * Attachments are not represented as fields on the DTO; they must be downloaded
 * separately via {@see \ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository::getAttachmentContent()}.
 *
 * All fields are nullable because this DTO is also used for partial construction
 * when creating new articles (not all fields are required by the API).
 */
final readonly class TicketArticleDTO implements DTOInterface
{
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public ?int $ticket_id = null,
        public ?string $type = null,
        public ?string $body = null,
        public ?string $content_type = null,
        public ?int $id = null,
        public ?DateTimeImmutable $created_at = null,
        public ?DateTimeImmutable $updated_at = null,
    ) {
    }
}
