<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ZammadAPIClient\Core\PaginatedList;
use ZammadAPIClient\Core\RequestHandler;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;

#[Group('unit')]
final class PaginatedListTest extends TestCase
{
    use \ZammadAPIClient\Tests\Unit\Core\Traits\CreatesRequestHandler;

    private const DTO_CLASS = TicketDTO::class;
    private const ENDPOINT   = 'tickets';
    private const PER_PAGE   = 2;

    private RequestHandler $handler;

    protected function setUp(): void
    {
        $this->setUpRequestHandler();

        $this->handler = $this->createHandler(
            new class implements ClientInterface {
                public function sendRequest(RequestInterface $request): ResponseInterface
                {
                    $uri = (string) $request->getUri();
                    preg_match('/page=(\d+)/', $uri, $m);
                    $page = (int) ($m[1] ?? 1);

                    $items = [
                        ['id' => $page * 10 + 1, 'title' => "Ticket a{$page}"],
                        ['id' => $page * 10 + 2, 'title' => "Ticket b{$page}"],
                    ];

                    return new Response(200, [], (string) json_encode(['tickets' => $items]));
                }
            },
        );
    }

    private function createList(string $endpoint = self::ENDPOINT): PaginatedList
    {
        return new PaginatedList($this->handler, self::DTO_CLASS, $endpoint, perPage: self::PER_PAGE);
    }

    public function testPageLoadsSinglePage(): void
    {
        $list = $this->createList();
        $list->page(2);

        $items = [];
        foreach ($list as $ticket) {
            $items[] = $ticket->id;
        }

        self::assertCount(2, $items);
        self::assertSame(21, $items[0]);
        self::assertSame(22, $items[1]);
    }

    public function testCountReturnsCurrentPageItemCount(): void
    {
        $list = $this->createList();
        $list->page(3);

        self::assertSame(2, count($list));
    }

    public function testFirstReturnsFirstItem(): void
    {
        $list = $this->createList();
        $list->page(1);

        $first = $list->first();

        self::assertNotNull($first);
        self::assertSame(11, $first->id);
    }

    public function testPageNextLoadsNextPage(): void
    {
        $list = $this->createList();
        $list->page(1);
        $list->pageNext();

        $items = [];
        foreach ($list as $ticket) {
            $items[] = $ticket->id;
        }

        self::assertSame(21, $items[0]);
    }

    public function testEachIteratesAllLoadedItems(): void
    {
        $list = $this->createList();
        $list->page(1);

        $count = 0;
        $list->each(function () use (&$count) {
            $count++;
        });

        self::assertSame(2, $count);
    }

    public function testOffsetAccessGetsItemByIndex(): void
    {
        $list = $this->createList();
        $list->page(1);

        self::assertNotNull($list[0]);
        self::assertNotNull($list[1]);
        self::assertSame(11, $list[0]->id);
    }

    public function testOffsetExistsDoesNotTriggerHttpForUncachedPage(): void
    {
        $httpClient = new class implements ClientInterface {
            public int $callCount = 0;
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                return new Response(200, [], (string) json_encode([
                    'tickets' => [['id' => 11, 'title' => 'a']],
                ]));
            }
        };

        $list = new PaginatedList(
            $this->createHandler($httpClient), self::DTO_CLASS, self::ENDPOINT, perPage: self::PER_PAGE,
        );

        $list->page(1);

        self::assertFalse(isset($list[5]));
        self::assertSame(1, $httpClient->callCount);
    }

    public function testGetTotalCountReturnsValueFromSearchEndpoint(): void
    {
        $httpClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response(200, [], (string) json_encode([
                    'records'     => [['id' => 11, 'title' => 'a']],
                    'total_count' => 42,
                ]));
            }
        };

        $list = new PaginatedList(
            $this->createHandler($httpClient), self::DTO_CLASS, self::ENDPOINT . '/search',
        );

        $list->page(1);

        self::assertSame(42, $list->getTotalCount());
        self::assertSame(1, $list->count());
    }

    public function testSearchListReturnsItemsOnAllPages(): void
    {
        $counter = ['count' => 0];
        $httpClient = new class ($counter) implements ClientInterface {
            /** @param array{count: int} $counter */
            public function __construct(private array $counter) {}
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->counter['count']++;
                $page = match ($this->counter['count']) {
                    1 => ['records' => [['id' => 1], ['id' => 2]], 'total_count' => 3],
                    2 => ['records' => [['id' => 3]],            'total_count' => 3],
                    default => ['records' => [], 'total_count' => 3],
                };
                return new Response(200, [], (string) json_encode($page));
            }
        };

        $list = new PaginatedList(
            $this->createHandler($httpClient), self::DTO_CLASS, self::ENDPOINT . '/search', perPage: self::PER_PAGE,
        );

        $list->page(1);
        self::assertSame(2, $list->count(), 'Page 1 should have 2 items');
        self::assertSame(3, $list->getTotalCount());

        $list->page(2);
        self::assertSame(1, $list->count(), 'Page 2 should have 1 item');
        self::assertSame(3, $list->getTotalCount());
    }

    public function testGetTotalCountReturnsNullOnIndexEndpoint(): void
    {
        $httpClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response(200, [], (string) json_encode([
                    'tickets' => [['id' => 11, 'title' => 'a']],
                ]));
            }
        };

        $list = new PaginatedList(
            $this->createHandler($httpClient), self::DTO_CLASS, self::ENDPOINT,
        );

        $list->page(1);

        self::assertNull($list->getTotalCount());
    }

    public function testInferListKeyStripsSearchSuffix(): void
    {
        $list = $this->createList();
        $list->page(1);

        $items = iterator_to_array($list);

        self::assertCount(2, $items);
    }

    public function testOffsetExistsReturnsTrueForCachedPage(): void
    {
        $list = $this->createList();
        $list->page(1);

        self::assertTrue(isset($list[0]));
        self::assertTrue(isset($list[1]));
    }
}
