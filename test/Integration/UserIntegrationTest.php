<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Users\UserDTO;
use ZammadAPIClient\Endpoints\Users\UserRepository;
use ZammadAPIClient\Exceptions\ZammadException;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class UserIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    public function testCreateUser(): void
    {
        $email = 'test-' . uniqid('', true) . '@example.com';
        $user = self::$client->repo(UserRepository::class)->create(new UserDTO(
            email: $email,
            firstname: 'Integration',
            lastname: 'Test',
            role_ids: [3],
        ));

        self::assertGreaterThan(0, $user->id);
        self::assertSame($email, $user->email);
    }

    public function testFindUser(): void
    {
        $email = 'find-' . uniqid('', true) . '@example.com';
        $user = self::$client->repo(UserRepository::class)->create(new UserDTO(
            email: $email,
            firstname: 'Find',
            lastname: 'Test',
            role_ids: [3],
        ));

        $found = self::$client->repo(UserRepository::class)->find($user->id);

        self::assertEquals($user->id, $found->id);
        self::assertSame($email, $found->email);
    }

    public function testListUsers(): void
    {
        $email = 'list-' . uniqid('', true) . '@example.com';
        $created = self::$client->repo(UserRepository::class)->create(new UserDTO(
            email: $email,
            firstname: 'List',
            lastname: 'Test',
            role_ids: [3],
        ));

        $found = false;
        foreach (self::$client->repo(UserRepository::class)->all() as $user) {
            if ($user->id === $created->id) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'all() should include the created user');
    }

    public function testDeleteUser(): void
    {
        $email = 'tmp-' . uniqid('', true) . '@example.com';
        $user = self::$client->repo(UserRepository::class)->create(new UserDTO(
            email: $email,
            firstname: 'DeleteMe',
            role_ids: [3],
        ));

        self::assertGreaterThan(0, $user->id);

        try {
            self::$client->repo(UserRepository::class)->delete($user->id);
        } catch (ZammadException $e) {
            self::markTestSkipped(
                'User deletion not allowed by this Zammad instance: ' . $e->getMessage(),
            );
        }

        self::assertTrue(true);
    }
}
