<?php

declare(strict_types=1);

namespace ZammadAPIClient\Resource;

class Group extends AbstractResource
{
    public const URLS = [
        'get'    => 'groups/{object_id}',
        'all'    => 'groups',
        'create' => 'groups',
        'update' => 'groups/{object_id}',
        'delete' => 'groups/{object_id}',
    ];
}
