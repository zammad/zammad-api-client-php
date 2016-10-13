<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class TicketPriority extends AbstractResource
{
    const URLS = [
        'get'    => 'ticket_priorities/{object_id}',
        'all'    => 'ticket_priorities',
        'create' => 'ticket_priorities',
        'update' => 'ticket_priorities/{object_id}',
        'delete' => 'ticket_priorities/{object_id}',
    ];
}
