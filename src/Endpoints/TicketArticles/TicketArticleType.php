<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TicketArticles;

/**
 * Enum of Zammad ticket article communication channel types.
 *
 * Used as the value for {@see TicketArticleDTO::$type} when creating
 * or updating a ticket article. The backing string matches what the
 * Zammad REST API expects.
 *
 * Usage:
 * ```php
 * $dto = new TicketArticleDTO(
 *     type: TicketArticleType::Note->value,
 * );
 * ```
 */
enum TicketArticleType: string
{
    /** Internal note — hidden from customers, visible to agents only. */
    case Note = 'note';

    /** Email communication (inbound or outbound). */
    case Email = 'email';

    /** Phone call log entry. */
    case Phone = 'phone';

    /** SMS message. */
    case Sms = 'sms';

    /** Web form or web interface interaction. */
    case Web = 'web';
}
