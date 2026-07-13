<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class TicketIntegrationTest extends TestCase
{
    private static ZammadClient $client;
    private static array $createdIds = [];

    public static function setUpBeforeClass(): void
    {
        $url   = getenv('ZAMMAD_URL') ?: null;
        $token = getenv('ZAMMAD_TOKEN') ?: null;
        $user  = getenv('ZAMMAD_USER') ?: null;
        $pass  = getenv('ZAMMAD_PASS') ?: null;

        if ($url === null || $url === '') {
            self::markTestSkipped(
                'Integration tests require ZAMMAD_URL environment variable. '
                . 'Run: ZAMMAD_URL=http://127.0.0.1:8080/api/v1 ZAMMAD_TOKEN=xxx vendor/bin/phpunit --group=integration'
            );
        }

        if ($token !== null && $token !== '') {
            self::$client = ZammadClient::connect($url, token: $token);
        } elseif ($user !== null && $pass !== null && $user !== '' && $pass !== '') {
            self::$client = ZammadClient::connect($url, user: $user, pass: $pass);
        } else {
            self::markTestSkipped(
                'Integration tests require ZAMMAD_TOKEN or ZAMMAD_USER+ZAMMAD_PASS environment variables. '
                . 'Run: ZAMMAD_URL=http://127.0.0.1:8080/api/v1 ZAMMAD_TOKEN=xxx vendor/bin/phpunit --group=integration'
            );
        }
    }

    public function testCreateTicket(): void
    {
        $ticket = self::$client->ticket()->create(new TicketDTO(
            id: null,
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            organization_id: null,
            customer_id: null,
            owner_id: null,
            title: 'Integration Test ' . uniqid('', true),
            number: null,
            created_at: null,
            updated_at: null,
        ));

        self::assertGreaterThan(0, $ticket->id);
        self::assertEquals(1, $ticket->group_id);
        self::assertNotEmpty($ticket->title);

        self::$createdIds[] = $ticket->id;
    }

    public function testFindTicket(): void
    {
        if (empty(self::$createdIds)) {
            $this->markTestSkipped('No ticket created — run testCreateTicket first.');
        }

        $id = self::$createdIds[0];
        $ticket = self::$client->ticket()->find($id);

        self::assertEquals($id, $ticket->id);
        self::assertNotEmpty($ticket->title);
    }

    public function testPatchTicket(): void
    {
        if (empty(self::$createdIds)) {
            $this->markTestSkipped('No ticket created — run testCreateTicket first.');
        }

        $id = self::$createdIds[0];
        $newTitle = 'Patched ' . uniqid('', true);

        $patched = self::$client->ticket()->patch($id, ['title' => $newTitle]);
        self::assertEquals($newTitle, $patched->title);
    }

    public function testDeleteTicket(): void
    {
        if (empty(self::$createdIds)) {
            $this->markTestSkipped('No ticket created — run testCreateTicket first.');
        }

        $id = self::$createdIds[0];
        self::$client->ticket()->delete($id);

        $this->expectException(NotFoundException::class);
        self::$client->ticket()->find($id);
    }
}
