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
     * Replaces an entire resource with the given DTO (full PUT).
     *
     * All writable fields of $dto are sent to the API; fields absent in the
     * DTO but present on the server will be overwritten with their defaults.
     * Use {@see self::patch()} when only a subset of fields should change.
     *
     * @throws \ZammadAPIClient\Exceptions\NotFoundException   If $id does not exist.
     * @throws \ZammadAPIClient\Exceptions\ValidationException If the API rejects the payload.
     * @return T
     */
    public function update(int $id, DTOInterface $dto): DTOInterface;

    /**
     * Partially updates a resource (PATCH semantics).
     *
     * Only the supplied $changes are sent; all other fields remain unchanged on
     * the server. $changes may be a plain array or any object that exposes a
     * `toPatchArray()` method (e.g. {@see \ZammadAPIClient\Endpoints\Tickets\TicketUpdateDTO}).
     *
     * @param array<string, mixed>|object $changes Fields to update.
     * @return T
     */
    public function patch(int $id, array|object $changes): DTOInterface;

    /**
     * Permanently deletes the resource with the given ID.
     *
     * Zammad does not support soft-delete via the API; this operation is
     * irreversible. No exception is thrown if the resource does not exist
     * (the API returns 200 in that case).
     *
     * @throws \ZammadAPIClient\Exceptions\AuthenticationException On permission errors.
     */
    public function delete(int $id): void;
}
