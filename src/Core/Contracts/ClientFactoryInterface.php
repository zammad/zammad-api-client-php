<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;

interface ClientFactoryInterface
{
    public function createHandler(): RequestHandlerInterface;
}
