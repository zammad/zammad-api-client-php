<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Repository\AbstractRepository;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\PatchableInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\Repository\Resource;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;

#[Group('unit')]
final class AbstractRepositoryTest extends MockeryTestCase
{
    /** @var class-string<TestRepository> */
    private string $repoClass;

    private RequestHandlerInterface|Mockery\MockInterface $handler;

    protected function setUp(): void
    {
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
    }

    private function repo(string $resourcePath = 'tickets', string $dtoClass = TicketDTO::class, int $pageSize = 100): TestRepository
    {
        return new TestRepository($this->handler, $resourcePath, $dtoClass, $pageSize);
    }

    public function testGetDtoClass(): void
    {
        $repo = $this->repo(dtoClass: TicketDTO::class);

        self::assertSame(TicketDTO::class, $repo->getDtoClass());
    }

    public function testFindReturnsHydratedDto(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets/1', ['expand' => 'true'])
            ->andReturn(['id' => 1, 'title' => 'Hello']);

        $ticket = $this->repo()->find(1);

        self::assertInstanceOf(TicketDTO::class, $ticket);
        self::assertSame(1, $ticket->id);
        self::assertSame('Hello', $ticket->title);
    }

    public function testResourceReturnsMutableWrapper(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets/1', ['expand' => 'true'])
            ->andReturn(['id' => 1, 'title' => 'Hello']);

        $resource = $this->repo()->resource(1);

        self::assertInstanceOf(Resource::class, $resource);
    }

    public function testListReturnsPaginatedList(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['expand' => 'true', 'page' => '1', 'per_page' => '100'])
            ->andReturn(['tickets' => [['id' => 1, 'title' => 'a']]]);

        $list = $this->repo()->list(['expand' => 'true']);
        $list->page(1);

        self::assertSame(1, $list->count());
    }

    public function testSearchListReturnsPaginatedList(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets/search', ['query' => 'term', 'page' => '1', 'per_page' => '100', 'with_total_count' => 'true'])
            ->andReturn(['records' => [['id' => 1, 'title' => 'a']]]);

        $list = $this->repo()->searchList('term');
        $list->page(1);

        self::assertSame(1, $list->count());
    }

    public function testAllPaginatesLazily(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['tickets' => [
                ['id' => 1, 'title' => 'a'],
                ['id' => 2, 'title' => 'b'],
            ]]);
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['page' => '2', 'per_page' => '2'])
            ->andReturn(['tickets' => [
                ['id' => 3, 'title' => 'c'],
            ]]);

        $items = iterator_to_array($this->repo(pageSize: 2)->all());

        self::assertCount(3, $items);
        self::assertContainsOnlyInstancesOf(TicketDTO::class, $items);
    }

    public function testSearchPaginatesLazily(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets/search', ['query' => 'term', 'page' => '1', 'per_page' => '1'])
            ->andReturn(['tickets' => [
                ['id' => 1, 'title' => 'a'],
            ]]);
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets/search', ['query' => 'term', 'page' => '2', 'per_page' => '1'])
            ->andReturn(['tickets' => []]);

        $items = iterator_to_array($this->repo(pageSize: 1)->search('term'));

        self::assertCount(1, $items);
        self::assertSame(1, $items[0]->id);
    }

    public function testAllPageFetchesExplicitPage(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['page' => '2', 'per_page' => '10'])
            ->andReturn(['tickets' => [
                ['id' => 11, 'title' => 'a'],
                ['id' => 12, 'title' => 'b'],
            ]]);

        $items = $this->repo()->allPage(2, 10);

        self::assertCount(2, $items);
        self::assertContainsOnlyInstancesOf(TicketDTO::class, $items);
    }

    public function testSearchPageFetchesExplicitSearchPage(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets/search', ['query' => 'term', 'page' => '1', 'per_page' => '5'])
            ->andReturn(['tickets' => [
                ['id' => 1, 'title' => 'a'],
            ]]);

        $items = $this->repo()->searchPage('term', 1, 5);

        self::assertCount(1, $items);
        self::assertSame(1, $items[0]->id);
    }

    public function testExtractItemsReturnsFilteredArray(): void
    {
        $repo = $this->repo();

        $data = [
            'tickets' => [
                ['id' => 1], 'not-an-array', ['id' => 2],
            ],
        ];

        $items = $repo->publicExtractItems($data);

        self::assertCount(2, $items);
        self::assertSame(1, $items[0]['id']);
        self::assertSame(2, $items[1]['id']);
    }

    public function testExtractItemsFallsBackToRawDataWhenKeyMissing(): void
    {
        $repo = $this->repo();

        $items = $repo->publicExtractItems([['id' => 1], ['id' => 2]]);

        self::assertCount(2, $items);
    }

    public function testExtractItemsWithCustomKey(): void
    {
        $repo = $this->repo();

        $items = $repo->publicExtractItems(
            ['articles' => [['id' => 1]]],
            'articles',
        );

        self::assertCount(1, $items);
        self::assertSame(1, $items[0]['id']);
    }

    public function testExtractItemsRemovesAssetsKey(): void
    {
        $repo = $this->repo();

        $items = $repo->publicExtractItems([
            ['id' => 1],
            'assets' => ['User' => [1 => ['id' => 1]]],
        ]);

        self::assertCount(1, $items);
        self::assertArrayNotHasKey('assets', $items);
    }

    public function testExtractItemsReturnsEmptyWhenNotArray(): void
    {
        $repo = $this->repo();

        $items = $repo->publicExtractItems(['tickets' => 'not-an-array']);

        self::assertCount(0, $items);
    }

    public function testGetIteratorDelegatesToAll(): void
    {
        $this->handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['page' => '1', 'per_page' => '100'])
            ->andReturn(['tickets' => []]);

        $count = 0;
        foreach ($this->repo() as $ticket) {
            $count++;
        }

        self::assertSame(0, $count);
    }

    public function testCreatePostsAndHydrates(): void
    {
        $dto = new TicketDTO(title: 'New Ticket', group_id: 1);

        $this->handler->shouldReceive('post')
            ->once()
            ->with('tickets', Mockery::type('array'))
            ->andReturn(['id' => 42, 'title' => 'New Ticket']);

        $result = $this->repo()->create($dto);

        self::assertInstanceOf(TicketDTO::class, $result);
        self::assertSame(42, $result->id);
    }

    public function testPatchWithDtoPutsAndHydrates(): void
    {
        $dto = new TicketDTO(title: 'Updated');

        $this->handler->shouldReceive('put')
            ->once()
            ->with('tickets/1', Mockery::type('array'))
            ->andReturn(['id' => 1, 'title' => 'Updated']);

        $result = $this->repo()->patch(1, $dto);

        self::assertInstanceOf(TicketDTO::class, $result);
        self::assertSame('Updated', $result->title);
    }

    public function testPatchWithArray(): void
    {
        $this->handler->shouldReceive('put')
            ->once()
            ->with('tickets/1', ['title' => 'Patched', 'state_id' => 2])
            ->andReturn(['id' => 1, 'title' => 'Patched']);

        $result = $this->repo()->patch(1, ['title' => 'Patched', 'state_id' => 2]);

        self::assertInstanceOf(TicketDTO::class, $result);
        self::assertSame('Patched', $result->title);
    }

    public function testPatchWithArrayFiltersNulls(): void
    {
        $this->handler->shouldReceive('put')
            ->once()
            ->with('tickets/1', ['title' => 'Patched'])
            ->andReturn(['id' => 1, 'title' => 'Patched']);

        $result = $this->repo()->patch(1, ['title' => 'Patched', 'state_id' => null]);

        self::assertInstanceOf(TicketDTO::class, $result);
    }

    public function testPatchWithPatchableInterface(): void
    {
        $patchable = new class implements PatchableInterface {
            public function toPatchArray(): array
            {
                return ['title' => 'From patchable', 'owner_id' => null];
            }
        };

        $this->handler->shouldReceive('put')
            ->once()
            ->with('tickets/1', ['title' => 'From patchable', 'owner_id' => null])
            ->andReturn(['id' => 1, 'title' => 'From patchable']);

        $result = $this->repo()->patch(1, $patchable);

        self::assertInstanceOf(TicketDTO::class, $result);
    }

    public function testPatchWithPlainObject(): void
    {
        $changes = new class {
            public string $title = 'Object patch';
            public ?int $state_id = null;
        };

        $this->handler->shouldReceive('put')
            ->once()
            ->with('tickets/1', ['title' => 'Object patch'])
            ->andReturn(['id' => 1, 'title' => 'Object patch']);

        $result = $this->repo()->patch(1, $changes);

        self::assertInstanceOf(TicketDTO::class, $result);
    }
}

final class TestRepository extends AbstractRepository
{
    protected function getListKey(): string
    {
        return 'tickets';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    public function publicExtractItems(array $data, ?string $key = null): array
    {
        return $this->extractItems($data, $key);
    }
}
