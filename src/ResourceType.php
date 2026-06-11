<?php

declare(strict_types=1);

namespace ZammadAPIClient;

class ResourceType
{
    public const ORGANIZATION    = '\\ZammadAPIClient\\Resource\\Organization';
    public const GROUP           = '\\ZammadAPIClient\\Resource\\Group';
    public const USER            = '\\ZammadAPIClient\\Resource\\User';
    public const TEXT_MODULE     = '\\ZammadAPIClient\\Resource\\TextModule';
    public const TICKET_STATE    = '\\ZammadAPIClient\\Resource\\TicketState';
    public const TICKET_PRIORITY = '\\ZammadAPIClient\\Resource\\TicketPriority';
    public const TICKET          = '\\ZammadAPIClient\\Resource\\Ticket';
    public const TICKET_ARTICLE  = '\\ZammadAPIClient\\Resource\\TicketArticle';
    public const TAG             = '\\ZammadAPIClient\\Resource\\Tag';
    public const LINK            = '\\ZammadAPIClient\\Resource\\Link';
}
