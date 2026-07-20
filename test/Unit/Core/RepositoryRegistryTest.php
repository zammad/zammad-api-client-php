<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Repository\RepositoryRegistry;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;

#[Group('unit')]
final class RepositoryRegistryTest extends TestCase
{
    public function testDefinitionReturnsPathAndDtoForRegisteredRepository(): void
    {
        $def = RepositoryRegistry::definition(TicketRepository::class);

        self::assertSame('tickets', $def['path']);
        self::assertSame(TicketDTO::class, $def['dto']);
    }

    public function testDefinitionThrowsForUnknownRepository(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository');

        RepositoryRegistry::definition('UnknownRepository');
    }

    public function testAllRegisteredRepositoriesHavePathAndDto(): void
    {
        foreach (RepositoryRegistry::DEFINITIONS as $repoClass => $def) {
            self::assertArrayHasKey('path', $def, "{$repoClass} missing path");
            self::assertArrayHasKey('dto', $def, "{$repoClass} missing dto");
            self::assertIsString($def['path']);
            self::assertIsString($def['dto']);
        }
    }
}
