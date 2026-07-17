<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use Psr\Log\LoggerInterface;

final class ConnectionConfig
{
    public function __construct(
        public readonly int $maxRetries = 3,
        public readonly bool $verifySsl = true,
        public readonly int $timeout = 30,
        public readonly int $connectTimeout = 10,
        public readonly ?LoggerInterface $logger = null,
    ) {
    }
}
