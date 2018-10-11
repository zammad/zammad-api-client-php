<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient;

class ResourceType
{
    const ORGANIZATION    = '\\ZammadAPIClient\\Resource\\Organization';
    const GROUP           = '\\ZammadAPIClient\\Resource\\Group';
    const USER            = '\\ZammadAPIClient\\Resource\\User';
    const TICKET_STATE    = '\\ZammadAPIClient\\Resource\\TicketState';
    const TICKET_PRIORITY = '\\ZammadAPIClient\\Resource\\TicketPriority';
    const TICKET          = '\\ZammadAPIClient\\Resource\\Ticket';
    const TICKET_ARTICLE  = '\\ZammadAPIClient\\Resource\\TicketArticle';
    const TAG             = '\\ZammadAPIClient\\Resource\\Tag';
}
