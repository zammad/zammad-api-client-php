<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Repository;

use BadMethodCallException;
use Generator;
use Traversable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\PatchableInterface;
use ZammadAPIClient\Core\Contracts\RepositoryInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;

/**
 * Base class for all Zammad endpoint repositories.
 *
 * Provides generic CRUD operations, cursor-based pagination, and the
 * `IteratorAggregate` shorthand (use a repository in `foreach` to stream all
 * resources). Concrete subclasses only need to:
 *
 *  1. Declare the generic type parameter (`@extends AbstractRepository<TicketDTO>`).
 *  2. Implement {@see self::getListKey()} to name the JSON array key that holds
 *     the resource list in paginated list responses (varies per endpoint).
 *  3. Optionally add endpoint-specific convenience methods (e.g. `getForTicket`).
 *
 * All repositories are instantiated via {@see \ZammadAPIClient\ZammadClient::repo()},
 * which injects the shared `RequestHandler` and the wiring defined in
 * {@see \ZammadAPIClient\Core\Repository\RepositoryRegistry::DEFINITIONS}.
 *
 * @template T of DTOInterface
 * @implements RepositoryInterface<T>
 */
abstract class AbstractRepository implements RepositoryInterface
{
    public const DEFAULT_PAGE_SIZE = 100;

    /**
     * @param RequestHandlerInterface $handler      Shared HTTP transport; injected by the client.
     * @param string                  $resourcePath API path segment for this resource (e.g. `tickets`).
     * @param class-string<T>         $dtoClass     DTO class used to hydrate API responses.
     * @param int                     $pageSize     Number of items fetched per page during cursor pagination.
     */
    public function __construct(
        protected RequestHandlerInterface $handler,
        protected string $resourcePath,
        protected string $dtoClass,
        protected int $pageSize = self::DEFAULT_PAGE_SIZE,
    ) {
    }

    /**
     * Returns the fully-qualified DTO class name associated with this repository.
     *
     * Useful when the caller needs to instantiate or inspect the DTO without
     * going through a repository method (e.g. in generic utility code).
     *
     * @return class-string<T>
     */
    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }

    /**
     * Returns the JSON array key that contains the resource list in paginated responses.
     *
     * Zammad's list endpoints wrap results in a keyed array (e.g. `{"tickets": [...], "assets": {...}}`).
     * Each endpoint uses a different key; the default is the resource path.
     * Subclasses may override this when the list key differs from the path.
     */
    protected function getListKey(): string
    {
        return $this->resourcePath;
    }

    /**
     * Fetches a single resource by its Zammad-assigned ID.
     *
     * The `expand=true` query parameter is appended automatically so that
     * Zammad resolves referenced objects (e.g. owner name instead of just ID)
     * and returns them inline. Without it some relation fields would be null.
     *
     * @throws \ZammadAPIClient\Exceptions\NotFoundException If no resource with $id exists.
     */
    public function find(int $id): DTOInterface
    {
        return $this->dtoClass::fromArray(
            $this->handler->get("{$this->resourcePath}/{$id}", ['expand' => 'true']),
        );
    }

    /**
     * Ruby-style stateful resource: fetch by ID, returns a mutable wrapper
     * with changes tracking.
     *
     *   $ticket = $client->ticket()->resource(1);
     *   $ticket->title = 'New';
     *   $ticket->save();
     *
     * @return Resource Resource wrapper with changes tracking
     */
    public function resource(int $id): Resource
    {
        return new Resource($this->find($id), $this->handler, $this->resourcePath);
    }

    /**
     * Ruby-style paginated list with page navigation.
     *
     *   $list = $client->ticket()->list();
     *   $list->page(2);
     *   $list->each(function ($t) { echo $t->title; });
     *
     * @param array<string, mixed> $query
     * @return PaginatedList<T>
     */
    public function list(array $query = []): PaginatedList
    {
        return new PaginatedList(
            $this->handler,
            $this->dtoClass,
            $this->resourcePath,
            $query,
            $this->pageSize,
            $this->getListKey(),
        );
    }

    /**
     * Ruby-style search list with page navigation.
     *
     * @param array<string, mixed> $query
     * @return PaginatedList<T>
     */
    public function searchList(string $term, array $query = []): PaginatedList
    {
        return new PaginatedList(
            $this->handler,
            $this->dtoClass,
            "{$this->resourcePath}/search",
            array_merge(['query' => $term], $query),
            $this->pageSize,
            $this->getListKey(),
        );
    }

    /**
     * Streams all resources using transparent cursor pagination.
     *
     * Pages are fetched lazily via a PHP generator: no data is loaded until
     * the caller iterates. A page is considered complete when fewer items than
     * $pageSize are returned, at which point iteration stops. This avoids an
     * extra request to check for an empty last page.
     *
     * @param array<string, mixed> $query Optional API query parameters applied to every page request.
     * @return Generator<int, T>
     */
    public function all(array $query = []): iterable
    {
        return $this->paginate("{$this->resourcePath}", $query);
    }

    /**
     * Searches resources using Zammad's full-text search and paginates lazily.
     *
     * $term is forwarded verbatim as the `query` API parameter. Additional
     * parameters (e.g. `limit`, `sort_by`) can be passed via $query.
     *
     * @param array<string, mixed> $query Optional API query parameters merged with the search term.
     * @return Generator<int, T>
     */
    public function search(string $term, array $query = []): iterable
    {
        return $this->paginate(
            "{$this->resourcePath}/search",
            array_merge(['query' => $term], $query),
        );
    }

    /**
     * Fetches exactly one explicit page of resources.
     *
     * Unlike {@see self::all()}, which uses transparent cursor pagination, this
     * method exposes the raw `page` / `per_page` parameters so that the legacy
     * client can replicate v2's explicit pagination behaviour.
     *
     * Not intended for use in new code — prefer {@see self::all()}.
     *
     * @param array<string, mixed> $query Optional API query parameters.
     * @return array<int, T>
     */
    public function allPage(int $page, int $perPage, array $query = []): array
    {
        $params = array_merge($query, [
            'page' => (string) $page,
            'per_page' => (string) $perPage,
        ]);

        return $this->hydrateList($this->handler->get($this->resourcePath, $params));
    }

    /**
     * Fetches exactly one explicit page of search results.
     *
     * The legacy counterpart to {@see self::search()}; exists to support v2's
     * explicit pagination. Not intended for new code.
     *
     * @param array<string, mixed> $query Optional API query parameters merged with the search term.
     * @return array<int, T>
     */
    public function searchPage(string $term, int $page, int $perPage, array $query = []): array
    {
        $params = array_merge(['query' => $term], $query, [
            'page' => (string) $page,
            'per_page' => (string) $perPage,
        ]);

        return $this->hydrateList($this->handler->get("{$this->resourcePath}/search", $params));
    }

    /**
     * @param array<string, mixed> $query
     * @return Generator<int, T>
     */
    protected function paginate(string $endpoint, array $query): Generator
    {
        return $this->paginateWith($endpoint, $this->dtoClass, $query);
    }

    /**
     * Paginates with a custom DTO class and list key.
     *
     * @template TD of DTOInterface
     * @param class-string<TD>     $dtoClass
     * @param array<string, mixed> $query
     * @return Generator<int, TD>
     */
    protected function paginateWith(
        string $endpoint,
        string $dtoClass,
        array $query,
        ?string $listKey = null,
    ): Generator {
        $page = 1;

        do {
            $params = array_merge($query, ['page' => (string) $page, 'per_page' => (string) $this->pageSize]);
            $items = $this->extractItems($this->handler->get($endpoint, $params), $listKey);

            foreach ($items as $item) {
                yield $dtoClass::fromArray($item);
            }

            $hasMore = count($items) === $this->pageSize;
            $page++;
        } while ($hasMore);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, T>
     */
    protected function hydrateList(array $data): array
    {
        $result = [];

        foreach ($this->extractItems($data) as $item) {
            $result[] = $this->dtoClass::fromArray($item);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    protected function extractItems(array $data, ?string $key = null): array
    {
        return ResponseParser::extractItems($data, $key ?? $this->getListKey());
    }

    /**
     * Allows using the repository directly in a `foreach` loop.
     *
     * Delegates to {@see self::all()} so `foreach ($repo as $dto)` is
     * equivalent to `foreach ($repo->all() as $dto)`.
     */
    public function getIterator(): Traversable
    {
        return $this->all();
    }

    /**
     * Returns the total number of resources via search.
     *
     * Delegates to `searchList('*')` which fetches the first page
     * with `with_total_count=true`. One API call, no memory overhead.
     *
     * Override this method if the endpoint does not support the
     * standard `/search` path (e.g. links, tags).
     */
    public function totalCount(): int
    {
        return $this->searchList('*')->page(1)->getTotalCount() ?? 0;
    }

    /**
     * Creates a new resource and returns the server-confirmed DTO.
     *
     * The $dto must not have an ID (id should be null). The returned DTO
     * will have the server-assigned `id`, `created_at`, and `updated_at` set.
     *
     * @throws \ZammadAPIClient\Exceptions\ValidationException If the payload is rejected by the API.
     */
    public function create(DTOInterface $dto): DTOInterface
    {
        return $this->dtoClass::fromArray($this->handler->post($this->resourcePath, $dto->toArray()));
    }

    /**
     * Updates a resource via HTTP PUT with only the supplied fields.
     *
     * $changes may be:
     *  - A DTO implementing {@see DTOInterface} (e.g. `TicketDTO`):
     *    all non-null writable fields are sent via `toArray()`.
     *  - An object implementing {@see PatchableInterface} (e.g. `TicketUpdateDTO`):
     *    the `toPatchArray()` method controls which fields are sent.
     *  - A plain `array<string, mixed>`: only non-null values are sent.
     *
     * Despite the method name, no HTTP PATCH verb is used — Zammad uses
     * PUT for all updates and merges the body with the existing resource.
     * Null values are excluded from all request bodies, so absent fields
     * are never overwritten.
     *
     * @param array<string, mixed>|object $changes Fields to change.
     *
     * @throws \ZammadAPIClient\Exceptions\NotFoundException   If $id does not exist.
     * @throws \ZammadAPIClient\Exceptions\ValidationException If the payload is rejected.
     */
    public function patch(int $id, array|object $changes): DTOInterface
    {
        if ($changes instanceof DTOInterface) {
            $body = $changes->toArray();
        } elseif ($changes instanceof PatchableInterface) {
            $body = $changes->toPatchArray();
        } elseif (is_object($changes)) {
            $body = array_filter(get_object_vars($changes), fn($v) => $v !== null);
        } else {
            $body = array_filter($changes, fn($v) => $v !== null);
        }

        return $this->dtoClass::fromArray($this->handler->put("{$this->resourcePath}/{$id}", $body));
    }

    /**
     * Deletes a resource by ID.
     *
     * Repositories that implement {@see \ZammadAPIClient\Core\Contracts\DeletableInterface}
     * override this with the actual API call. Repositories without the interface
     * inherit this default, which throws a catchable exception instead of
     * causing a fatal error.
     *
     * @throws BadMethodCallException If the repository does not support deletion.
     */
    public function delete(int $id): void
    {
        throw new BadMethodCallException(
            static::class . ' does not support delete().',
        );
    }
}
