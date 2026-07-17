<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\Exceptions\ForbiddenException;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Exceptions\ValidationException;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class ErrorHandlingIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    /**
     * Verifies NotFoundException is thrown for a non-existent resource.
     */
    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        self::$client->repo(TicketRepository::class)->find(99999999);
    }

    /**
     * Verifies ValidationException is thrown for an invalid payload.
     */
    public function testValidationThrowsException(): void
    {
        $this->expectException(ValidationException::class);

        self::$client->repo(TicketRepository::class)->create(new \ZammadAPIClient\Endpoints\Tickets\TicketDTO(
            title: '', // empty title should fail validation
            group_id: 1,
            customer_id: 1,
        ));
    }
}
