<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ZammadAPIClient\Core\ConnectionConfig;

#[Group('unit')]
final class ConnectionConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new ConnectionConfig();

        self::assertSame(3, $config->maxRetries);
        self::assertTrue($config->verifySsl);
        self::assertSame(30, $config->timeout);
        self::assertSame(10, $config->connectTimeout);
        self::assertNull($config->logger);
    }

    public function testCustomValues(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $config = new ConnectionConfig(
            maxRetries: 5,
            verifySsl: false,
            timeout: 60,
            connectTimeout: 20,
            logger: $logger,
        );

        self::assertSame(5, $config->maxRetries);
        self::assertFalse($config->verifySsl);
        self::assertSame(60, $config->timeout);
        self::assertSame(20, $config->connectTimeout);
        self::assertSame($logger, $config->logger);
    }
}
