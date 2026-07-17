<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

use IteratorAggregate;

/**
 * Generic CRUD contract for a single Zammad API endpoint.
 *
 * Every endpoint repository implements this interface, typed to its specific
 * DTO (e.g. `RepositoryInterface<TicketDTO>`). The generic parameter is
 * covariant so a `RepositoryInterface<TicketDTO>` can be passed where a
 * `RepositoryInterface<DTOInterface>` is expected.
 *
 * The interface extends `IteratorAggregate` so that repositories can be used
 * in `foreach` loops directly — equivalent to calling {@see self::all()}.
 *
 * @template-covariant T of DTOInterface
 * @extends IteratorAggregate<int, T>
 */
interface RepositoryInterface extends IteratorAggregate
{
    /**
     * Fetches a single resource by server-assigned ID.
     *
     * @throws \ZammadAPIClient\Exceptions\NotFoundException    If no resource with $id exists.
     * @throws \ZammadAPIClient\Exceptions\AuthenticationException On authentication failure.
     * @return T
     */
    public function find(int $id): DTOInterface;

    /**
     * Returns all resources, lazily fetched page by page.
     *
     * The iterable is backed by a generator that yields one page at a time via
     * cursor pagination. Do not convert to an array on large datasets without
     * applying a limit first.
     *
     * @param array<string, mixed> $query Optional API query parameters (e.g. filters).
     * @return iterable<T>
     */
    public function all(array $query = []): iterable;

    /**
     * Returns resources matching the full-text search term.
     *
     * Zammad's search endpoint is used; $term is passed verbatim. Additional
     * API parameters (e.g. `limit`) can be supplied via $query.
     *
     * @param array<string, mixed> $query Optional API query parameters.
     * @return iterable<T>
     */
    public function search(string $term, array $query = []): iterable;

    /**
     * Creates a new resource and returns the server-confirmed state.
     *
     * The $dto must not have an ID set. The returned DTO will have the
     * server-assigned ID, created_at, and updated_at values populated.
     *
     * @throws \ZammadAPIClient\Exceptions\ValidationException If the API rejects the payload.
     * @return T
     */
    public function create(DTOInterface $dto): DTOInterface;

    /**
     * Updates a resource via HTTP PUT with only the supplied fields.
     *
     * Only the supplied $changes are sent; all other fields remain unchanged
     * on the server. Despite the method name, no HTTP PATCH verb is used —
     * Zammad uses PUT for all updates and merges the body with the existing
     * resource. Null values are excluded from all request bodies.
     *
     * $changes may be:
     *  - A plain `array<string, mixed>`: only non-null values are sent.
     *  - A {@see TicketDTO}: all non-null writable fields are sent (via `toArray()`).
     *  - An object implementing {@see PatchableInterface} (e.g. `TicketUpdateDTO`):
     *    the `toPatchArray()` method controls which fields are sent.
     *
     * @param array<string, mixed>|object $changes Fields to update.
     * @return T
     */
    public function patch(int $id, array|object $changes): DTOInterface;
}
