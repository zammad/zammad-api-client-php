<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Users\UserDTO;
use ZammadAPIClient\Endpoints\Users\UserRepository;

#[Group('unit')]
final class UserRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesUsers(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('users', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['users' => [
                ['id' => 1, 'email' => 'a@b.com'],
                ['id' => 2, 'email' => 'c@d.com'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('users', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new UserRepository($handler, 'users', UserDTO::class, 2);
        $users = iterator_to_array($repo->all());

        self::assertCount(2, $users);
        self::assertContainsOnlyInstancesOf(UserDTO::class, $users);
    }

    public function testFindReturnsUserDto(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('users/42', ['expand' => 'true'])
            ->andReturn(['id' => 42, 'email' => 'test@test.com']);

        $repo = new UserRepository($handler, 'users', UserDTO::class);
        $user = $repo->find(42);

        self::assertSame(42, $user->id);
        self::assertSame('test@test.com', $user->email);
    }

    public function testImportPostsCsv(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('users/import', ['data' => "name,email\na,a@b.com"])
            ->andReturn([]);

        $repo = new UserRepository($handler, 'users', UserDTO::class);
        $repo->import("name,email\na,a@b.com");

        self::assertTrue(true);
    }

    public function testDeleteDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('delete')
            ->with('users/42')
            ->once()
            ->andReturn([]);

        $repo = new UserRepository($handler, 'users', UserDTO::class);
        $repo->delete(42);
    }
}
