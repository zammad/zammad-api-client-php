<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class User extends AbstractResource
{
    const URLS = [
        'get'    => 'users/{object_id}',
        'all'    => 'users',
        'create' => 'users',
        'update' => 'users/{object_id}',
        'delete' => 'users/{object_id}',
        'search' => 'users/search',
    ];
}
