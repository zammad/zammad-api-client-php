<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketArticles;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad ticket article resource (`/api/v1/ticket_articles`).
 *
 * A ticket article is a single message within a ticket's communication thread.
 * It can represent an inbound email, an outbound reply, an internal note,
 * a phone call log, or any other communication channel Zammad supports.
 *
 * Read-only fields (set by Zammad):
 *  - `id`             — Server-assigned primary key.
 *  - `type_id`        — Numeric ID of the article type (resolved to `type` name).
 *  - `sender_id`      — Numeric ID of the sender type (resolved to `sender` name).
 *  - `created_by_id`  — ID of the user who created the article.
 *  - `updated_by_id`  — ID of the user who last modified the article.
 *  - `created_by`     — Identifier string of the creator.
 *  - `updated_by`     — Identifier string of the last modifier.
 *  - `sender`         — Display name of the sender category (e.g. "Customer", "Agent").
 *  - `time_unit`      — Time accounting value (float, in minutes).
 *
 * Timestamp fields (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 *
 * Writable fields (used when creating or updating):
 *  - `ticket_id`      — The parent ticket this article belongs to.
 *  - `type`           — Channel/type string (e.g. `'note'`, `'email'`, `'phone'`).
 *  - `body`           — The content of the message.
 *  - `content_type`   — MIME type of the body (`'text/plain'` or `'text/html'`).
 *  - `subject`        — Subject line (used for email-type articles).
 *  - `from`           — Sender address or display name.
 *  - `to`             — Recipient address (email-type articles).
 *  - `cc`             — CC address (email-type articles).
 *  - `internal`       — Whether the article is an internal note (hidden from customers).
 *  - `in_reply_to`    — Message-ID for email threading.
 *  - `reply_to`       — Reply-To address for emails.
 *  - `message_id`     — Message-ID of this article (for email threading).
 *  - `origin_by_id`   — User ID who created the article (for impersonation).
 *
 * Attachments are represented as an array of arrays, each with keys:
 *  - `filename` (string) — The file name.
 *  - `data`     (string) — Base64-encoded file content.
 *  - `mime-type` (string, optional) — MIME type of the file.
 *
 * Download attachment content via {@see TicketArticleRepository::getAttachmentContent()}.
 *
 * All fields are nullable because this DTO is also used for partial construction
 * when creating new articles (not all fields are required by the API).
 */
final class TicketArticleDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    /**
     * @param array<int, array{filename: string, data: string, mime-type?: string}>|null $attachments
     */
    public function __construct(
        public readonly ?int $ticket_id = null,
        public readonly ?string $type = null,
        public readonly ?string $body = null,
        public readonly ?string $content_type = null,
        public readonly ?string $subject = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?string $cc = null,
        public readonly ?bool $internal = null,
        public readonly ?string $in_reply_to = null,
        public readonly ?string $reply_to = null,
        public readonly ?string $message_id = null,
        public readonly ?int $origin_by_id = null,
        public readonly ?string $sender = null,
        public readonly ?int $type_id = null,
        public readonly ?int $sender_id = null,
        public readonly ?int $created_by_id = null,
        public readonly ?int $updated_by_id = null,
        public readonly ?string $created_by = null,
        public readonly ?string $updated_by = null,
        public readonly ?float $time_unit = null,
        public readonly ?array $attachments = null,
        public readonly ?int $id = null,
    ) {
    }
}
