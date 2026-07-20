<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

/**
 * Stateless service that fetches a single page of DTOs from a backend.
 *
 * The fetcher is responsible for HTTP communication and response parsing;
 * {@see \ZammadAPIClient\Core\Repository\PaginatedList} owns caching and iteration logic.
 *
 * Implementations may be replaced for testing (mock), caching (decorator), or
 * alternative backends without touching the list logic.
 *
 * @template T of DTOInterface
 */
interface PageFetcherInterface
{
    /**
     * Fetches one page of items and an optional total count.
     *
     * @param int                    $page      Page number (1-indexed).
     * @param int                    $perPage   Items per page.
     * @param array<string, mixed>   $baseQuery Additional query params (e.g. search term).
     *
     * @return array{items: list<T>, total_count: ?int}
     */
    public function fetch(int $page, int $perPage, array $baseQuery): array;
}
