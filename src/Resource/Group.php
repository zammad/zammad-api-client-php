<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class Group extends AbstractResource
{
    const URLS = [
        'get'    => 'groups/{object_id}',
        'all'    => 'groups',
        'create' => 'groups',
        'update' => 'groups/{object_id}',
        'delete' => 'groups/{object_id}',
    ];
}
