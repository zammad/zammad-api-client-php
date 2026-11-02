<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Transport;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\PageFetcherInterface;
use ZammadAPIClient\Core\Repository\ResponseParser;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;

/**
 * Default {@see PageFetcherInterface} implementation using the Zammad HTTP API.
 *
 * Detects search endpoints (`/search` suffix) and adjusts the request
 * accordingly: `with_total_count=true` is sent and the response is parsed
 * from the `records` key (wrapped format). Index endpoints use the
 * configured `listKey` with a bare-array fallback.
 *
 * This class is stateless except for its constructor dependencies;
 * caching and total-count storage are the caller's responsibility.
 *
 * @template T of DTOInterface
 * @implements PageFetcherInterface<T>
 */
final class HttpPageFetcher implements PageFetcherInterface
{
    /**
     * @param RequestHandlerInterface  $handler
     * @param class-string<DTOInterface> $dtoClass
     * @param string                   $endpoint  URL path (e.g. 'tickets', 'tickets/search').
     * @param ?string                  $listKey   JSON array key for index endpoints.
     */
    public function __construct(
        private RequestHandlerInterface $handler,
        private string $dtoClass,
        private string $endpoint,
        private ?string $listKey = null,
    ) {
    }

    /** @return array{items: list<T>, total_count: ?int} */
    public function fetch(int $page, int $perPage, array $baseQuery): array
    {
        $params = array_merge($baseQuery, [
            'page'     => (string) $page,
            'per_page' => (string) $perPage,
        ]);

        $isSearch = str_contains($this->endpoint, '/search');
        if ($isSearch) {
            $params['with_total_count'] = 'true';
        }

        $data = $this->handler->get($this->endpoint, $params);

        $fields = ResponseParser::fields($data);

        return $isSearch
            ? $this->extractSearchResults($fields)
            : $this->extractIndexResults($fields);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{items: list<T>, total_count: ?int}
     */
    private function extractSearchResults(array $data): array
    {
        $raw = $data['total_count'] ?? 0;
        $total = is_numeric($raw) ? (int) $raw : 0;
        $items = $data['records'] ?? [];

        return [
            'items'       => is_array($items) ? $this->hydrateItems($items) : [],
            'total_count' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{items: list<T>, total_count: ?int}
     */
    private function extractIndexResults(array $data): array
    {
        $listKey = $this->listKey ?? $this->inferListKey();
        $raw = $data['total_count'] ?? null;
        $total = is_numeric($raw) ? (int) $raw : null;

        return [
            'items'       => $this->hydrateItems(ResponseParser::extractItems($data, $listKey)),
            'total_count' => $total,
        ];
    }

    /**
     * Hydrates whatever item arrays are in $items, ignoring everything else.
     *
     * The parameter is deliberately as loose as the method's own filtering:
     * search responses hand over a raw `records` value, and claiming a narrower
     * type here would only move the unchecked cast to the caller.
     *
     * @param array<array-key, mixed> $items
     * @return list<T>
     */
    private function hydrateItems(array $items): array
    {
        $hydrated = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $fields = [];
            foreach ($item as $key => $value) {
                $fields[(string) $key] = $value;
            }

            $hydrated[] = $this->dtoClass::fromArray($fields);
        }

        /** @var list<T> */
        return $hydrated;
    }

    private function inferListKey(): string
    {
        $endpoint = trim($this->endpoint, '/');
        $endpoint = (string) preg_replace('#/search$#', '', $endpoint);
        $parts = explode('/', $endpoint);

        return (string) end($parts);
    }
}
