<?php

declare(strict_types=1);

namespace ZammadAPIClient\Resource;

class TicketPriority extends AbstractResource
{
    public const URLS = [
        'get'    => 'ticket_priorities/{object_id}',
        'all'    => 'ticket_priorities',
        'create' => 'ticket_priorities',
        'update' => 'ticket_priorities/{object_id}',
        'delete' => 'ticket_priorities/{object_id}',
    ];
}
