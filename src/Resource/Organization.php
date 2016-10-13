<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class Organization extends AbstractResource
{
    const URLS = [
        'get'    => 'organizations/{object_id}',
        'all'    => 'organizations',
        'create' => 'organizations',
        'update' => 'organizations/{object_id}',
        'delete' => 'organizations/{object_id}',
        'search' => 'organizations/search',
    ];
}
