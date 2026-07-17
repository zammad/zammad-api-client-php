<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\TextModules\TextModuleDTO;
use ZammadAPIClient\Endpoints\TextModules\TextModuleRepository;

#[Group('unit')]
final class TextModuleRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesTextModules(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('text_modules', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['text_modules' => [
                ['id' => 1, 'name' => 'Greeting'],
                ['id' => 2, 'name' => 'Closing'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('text_modules', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new TextModuleRepository($handler, 'text_modules', TextModuleDTO::class, 2);
        $modules = iterator_to_array($repo->all());

        self::assertCount(2, $modules);
        self::assertContainsOnlyInstancesOf(TextModuleDTO::class, $modules);
    }

    public function testImportPostsCsv(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('text_modules/import', ['data' => "name\nGreeting"])
            ->andReturn([]);

        $repo = new TextModuleRepository($handler, 'text_modules', TextModuleDTO::class);
        $repo->import("name\nGreeting");

        self::assertTrue(true);
    }

    public function testDeleteDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('delete')
            ->with('text_modules/3')
            ->once()
            ->andReturn([]);

        $repo = new TextModuleRepository($handler, 'text_modules', TextModuleDTO::class);
        $repo->delete(3);
    }
}
