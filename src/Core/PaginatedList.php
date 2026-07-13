<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use ArrayAccess;
use Countable;
use Iterator;
use ZammadAPIClient\Core\Contracts\DTOInterface;
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

    private int $position = 0;

    /**
     * @param RequestHandlerInterface     $handler
     * @param class-string<T>             $dtoClass
     * @param string                      $endpoint    URL path (e.g. 'tickets', 'tickets/search')
     * @param array<string, mixed>        $baseQuery   Base query params (e.g. ['query' => 'term'])
     * @param int                         $perPage
     */
    public function __construct(
        private RequestHandlerInterface $handler,
        private string $dtoClass,
        private string $endpoint,
        private array $baseQuery = [],
        private int $perPage = 100,
        private ?string $listKey = null,
    ) {
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

        return $this;
    }

    /** @return self<T> */
    public function pageNext(): self
    {
        $currentPage = $this->position > 0
            ? (int) ceil(count($this->items) > 0 ? (array_key_last($this->items) + 1) / $this->perPage : 1)
            : 1;

        return $this->page($currentPage + 1);
    }

    /** @return self<T> */
    public function pagePrev(): self
    {
        $currentPage = $this->position > 0
            ? (int) ceil(count($this->items) > 0 ? (array_key_last($this->items) + 1) / $this->perPage : 1)
            : 2;

        return $this->page(max(1, $currentPage - 1));
    }

    public function each(callable $callback): void
    {
        foreach ($this as $item) {
            $callback($item);
        }
    }

    public function count(): int
    {
        return count($this->items);
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
            $this->items = $this->fetchPage(1);
        }
    }

    /**
     * @param int $page
     * @return list<T>
     */
    private function fetchPage(int $page): array
    {
        $params = array_merge($this->baseQuery, [
            'page'     => (string) $page,
            'per_page' => (string) $this->perPage,
        ]);

        $data = $this->handler->get($this->endpoint, $params);

        $items = $data[$this->listKey ?? $this->inferListKey()] ?? [];

        if (!is_array($items)) {
            return [];
        }

        /** @var list<T> */
        return array_map(
            fn(array $item): DTOInterface => $this->dtoClass::fromArray($item),
            array_values(array_filter($items, 'is_array')),
        );
    }

    private function inferListKey(): string
    {
        $parts = explode('/', trim($this->endpoint, '/'));

        return end($parts);
    }
}
