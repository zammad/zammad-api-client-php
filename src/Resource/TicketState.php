<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class TicketState extends AbstractResource
{
    const URLS = [
        'get'    => 'ticket_states/{object_id}',
        'all'    => 'ticket_states',
        'create' => 'ticket_states',
        'update' => 'ticket_states/{object_id}',
        'delete' => 'ticket_states/{object_id}',
    ];
}
