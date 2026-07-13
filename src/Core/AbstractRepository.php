<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use Generator;
use Traversable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
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
 * {@see \ZammadAPIClient\Core\RepositoryRegistry::DEFINITIONS}.
 *
 * @template T of DTOInterface
 * @implements RepositoryInterface<T>
 */
abstract class AbstractRepository implements RepositoryInterface
{
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
        protected int $pageSize = 100,
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
     * Each endpoint uses a different key; subclasses declare the correct one here so
     * {@see self::extractItems()} can locate the items without guessing.
     */
    abstract protected function getListKey(): string;

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
        $dto = $this->dtoClass::fromArray(
            $this->handler->get("{$this->resourcePath}/{$id}", ['expand' => 'true']),
        );

        return new Resource($dto, $this->handler, $this->resourcePath);
    }

    /**
     * Ruby-style paginated list with page navigation.
     *
     *   $list = $client->ticket()->list();
     *   $list->page(2);
     *   $list->each(fn($t) => echo $t->title);
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
     * client can replicate v5's explicit pagination behaviour.
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
     * The legacy counterpart to {@see self::search()}; exists to support v5's
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
    private function paginate(string $endpoint, array $query): Generator
    {
        $page = 1;

        do {
            $params = array_merge($query, ['page' => (string) $page, 'per_page' => (string) $this->pageSize]);
            $items = $this->extractItems($this->handler->get($endpoint, $params));

            foreach ($items as $item) {
                yield $this->dtoClass::fromArray($item);
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
        $items = $data[$key ?? $this->getListKey()] ?? [];

        if (!is_array($items)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $filtered */
        $filtered = array_values(array_filter($items, 'is_array'));

        return $filtered;
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
     * Replaces a resource entirely via a full PUT request.
     *
     * All writable fields from $dto are sent; server-assigned fields
     * (`id`, `created_at`, `updated_at`) included in `toArray()` are silently
     * ignored by the Zammad API. The returned DTO is hydrated from the
     * server's response, reflecting any server-side transformations.
     *
     * For partial updates (only a few fields), use {@see self::patch()} to
     * avoid accidentally overwriting fields the caller did not intend to change.
     *
     * @throws \ZammadAPIClient\Exceptions\NotFoundException   If $id does not exist.
     * @throws \ZammadAPIClient\Exceptions\ValidationException If the payload is rejected.
     */
    public function update(int $id, DTOInterface $dto): DTOInterface
    {
        return $this->dtoClass::fromArray(
            $this->handler->put("{$this->resourcePath}/{$id}", $dto->toArray()),
        );
    }

    /**
     * Partially updates a resource (PUT with a reduced field set).
     *
     * $changes may be:
     *  - A plain `array<string, mixed>`: only non-null values are sent.
     *  - An object with a `toPatchArray()` method (e.g. `TicketUpdateDTO`):
     *    the method's return value is sent verbatim, allowing explicit nulling.
     *  - Any other object: its public properties are serialised via
     *    `get_object_vars()` and null values are filtered out.
     *
     * Zammad uses PUT for both full and partial updates; only the supplied
     * fields are changed on the server side because Zammad merges the body
     * with the existing resource.
     *
     * @param array<string, mixed>|object $changes Fields to change.
     */
    public function patch(int $id, array|object $changes): DTOInterface
    {
        if (is_object($changes) && method_exists($changes, 'toPatchArray')) {
            /** @var array<string, mixed> $body */
            $body = $changes->toPatchArray();
        } elseif (is_object($changes)) {
            $body = array_filter(get_object_vars($changes), fn($v) => $v !== null);
        } else {
            $body = array_filter($changes, fn($v) => $v !== null);
        }

        return $this->dtoClass::fromArray($this->handler->put("{$this->resourcePath}/{$id}", $body));
    }

    /**
     * Permanently deletes the resource with the given ID.
     *
     * The operation is irreversible. Zammad returns HTTP 200 with the deleted
     * resource's data; this method discards that response body. No exception is
     * raised if the resource does not exist (Zammad returns 200 regardless).
     *
     * @throws \ZammadAPIClient\Exceptions\AuthenticationException If the caller lacks permission.
     */
    public function delete(int $id): void
    {
        $this->handler->delete("{$this->resourcePath}/{$id}");
    }
}
