<?php

declare(strict_types=1);

namespace ZammadAPIClient\Resource;

class TicketState extends AbstractResource
{
    public const URLS = [
        'get'    => 'ticket_states/{object_id}',
        'all'    => 'ticket_states',
        'create' => 'ticket_states',
        'update' => 'ticket_states/{object_id}',
        'delete' => 'ticket_states/{object_id}',
    ];
}
