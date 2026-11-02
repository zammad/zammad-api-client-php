<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Repository;

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
     * Narrows a decoded response body to the string-keyed shape DTOs expect.
     *
     * A JSON object decodes to string keys, but a decoded body is typed
     * `array<array-key, mixed>` because Zammad answers some endpoints with a
     * top-level JSON array, which decodes to integer keys. Single-resource
     * endpoints always answer with an object, so this states that expectation
     * at the one place it is made instead of asserting it silently at each
     * call site.
     *
     * @param array<array-key, mixed> $data
     * @return array<string, mixed>
     */
    public static function fields(array $data): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            $fields[(string) $key] = $value;
        }

        return $fields;
    }

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
