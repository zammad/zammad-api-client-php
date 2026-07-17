<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use ArrayAccess;
use Countable;
use Iterator;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\PageFetcherInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;

/**
 * @template T of DTOInterface
 * @implements ArrayAccess<int, T>
 * @implements Iterator<int, T>
 */
final class PaginatedList implements ArrayAccess, Countable, Iterator
{
    /** @var list<T> */
    private array $items = [];

    /** @var array<int, list<T>> */
    private array $pageCache = [];

    private int $position = 0;
    private int $currentPage = 1;

    private ?int $totalCount = null;

    /** @var PageFetcherInterface<T> */
    private PageFetcherInterface $fetcher;

    /**
     * @param RequestHandlerInterface     $handler
     * @param class-string<T>             $dtoClass
     * @param string                      $endpoint    URL path (e.g. 'tickets', 'tickets/search')
     * @param array<string, mixed>        $baseQuery   Base query params (e.g. ['query' => 'term'])
     * @param int                         $perPage
     * @param ?string                     $listKey
     * @param ?PageFetcherInterface<T>  $fetcher     Custom fetcher; defaults to {@see HttpPageFetcher}.
     */
    public function __construct(
        RequestHandlerInterface $handler,
        string $dtoClass,
        string $endpoint,
        private array $baseQuery = [],
        private int $perPage = 100,
        ?string $listKey = null,
        ?PageFetcherInterface $fetcher = null,
    ) {
        // @phpstan-ignore assign.propertyType
        $this->fetcher = $fetcher ?? new HttpPageFetcher($handler, $dtoClass, $endpoint, $listKey);
    }

    /** @return T|null */
    public function first(): ?DTOInterface
    {
        return $this->offsetGet(0);
    }

    /** @return T|null */
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_int($offset)) {
            return null;
        }

        $page = (int) floor($offset / $this->perPage) + 1;
        $index = $offset % $this->perPage;

        $items = $this->fetchPage($page);

        return $items[$index] ?? null;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_int($offset)) {
            return false;
        }

        $page = (int) floor($offset / $this->perPage) + 1;

        if (!isset($this->pageCache[$page])) {
            return false;
        }

        return $this->offsetGet($offset) !== null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('PaginatedList is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('PaginatedList is read-only.');
    }

    /** @return self<T> */
    public function page(int $number): self
    {
        $this->items = $this->fetchPage($number);
        $this->position = 0;
        $this->currentPage = $number;

        return $this;
    }

    /** @return self<T> */
    public function pageNext(): self
    {
        return $this->page($this->currentPage + 1);
    }

    /** @return self<T> */
    public function pagePrev(): self
    {
        return $this->page(max(1, $this->currentPage - 1));
    }

    public function each(callable $callback): void
    {
        $this->rewind();

        foreach ($this as $item) {
            $callback($item);
        }
    }

    /**
     * Returns the number of items on the current page.
     *
     * This is NOT the total count across all pages. Use {@see self::getTotalCount()}
     * to retrieve the total from the API's `total_count` response field
     * (requires a page to have been fetched first).
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns the total number of items across all pages, or null if not yet
     * known (no page has been fetched yet). The value is read from the
     * `total_count` JSON field in the API response body.
     */
    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    /** @return T|null */
    public function current(): mixed
    {
        return $this->items[$this->position] ?? null;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        $this->ensurePageLoaded();

        return $this->position < count($this->items);
    }

    private function ensurePageLoaded(): void
    {
        if (empty($this->items)) {
            $this->items = $this->fetchPage($this->currentPage);
        }
    }

    /**
     * @param int $page
     * @return list<T>
     */
    private function fetchPage(int $page): array
    {
        if (isset($this->pageCache[$page])) {
            return $this->pageCache[$page];
        }

        $result = $this->fetcher->fetch($page, $this->perPage, $this->baseQuery);

        if ($this->totalCount === null && $result['total_count'] !== null) {
            $this->totalCount = $result['total_count'];
        }

        return $this->pageCache[$page] = $result['items'];
    }
}
