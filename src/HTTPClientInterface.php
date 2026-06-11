<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use Psr\Http\Message\ResponseInterface;

interface HTTPClientInterface
{
    public function request(string $method, $uri = '', array $options = []): ResponseInterface;
}
