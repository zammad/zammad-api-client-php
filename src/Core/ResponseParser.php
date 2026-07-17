<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

/**
 * Extracts and normalises DTO items from raw Zammad API response arrays.
 *
 * Zammad's list endpoints wrap items in a named key (e.g. `tickets`,
 * `users`), occasionally include an `assets` envelope, and may return
 * a bare array for search endpoints. This utility handles all three
 * response shapes uniformly.
 *
 * Used by both {@see AbstractRepository} (generator-based pagination)
 * and {@see HttpPageFetcher} (PaginatedList-based pagination).
 */
final class ResponseParser
{
    /**
     * Extracts item arrays from an API response.
     *
     * @param array<string, mixed> $data   Raw JSON-decoded API response.
     * @param string               $listKey Expected list key (e.g. `'tickets'`).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function extractItems(array $data, string $listKey): array
    {
        $items = $data[$listKey] ?? null;

        if ($items === null && !array_key_exists($listKey, $data)) {
            $items = $data;
            unset($items['assets']);
        }

        if (!is_array($items)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> */
        return array_values(array_filter($items, 'is_array'));
    }
}
