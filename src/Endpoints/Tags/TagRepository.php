<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Tags;

use Generator;
use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/tags` endpoint.
 *
 * Tags in Zammad are string labels that can be attached to any taggable object
 * (most commonly tickets). Unlike other endpoints, the tag API is object-scoped:
 * listing tags requires an `object` type (e.g. `'Ticket'`) and an `o_id`
 * (the specific object's ID). Adding and removing tags also requires both.
 *
 * The generic `search()` method is re-routed to the separate `/tag_search`
 * autocomplete endpoint, which returns matching tag names globally (not
 * restricted to a specific object).
 *
 * The standard `create()`, `update()`, `patch()`, and `delete()` methods
 * inherited from `AbstractRepository` operate on the tag object definitions
 * (admin-level); use {@see self::add()} and {@see self::remove()} to attach
 * or detach tags from specific objects at the application level.
 *
 * @extends AbstractRepository<TagDTO>
 */
final class TagRepository extends AbstractRepository
{
    /**
     * Returns 'tags' — the JSON array key in Zammad's tag list response.
     */
    protected function getListKey(): string
    {
        return 'tags';
    }

    /**
     * Streams tags for a specific object, paginated.
     *
     * Overrides the generic `all()` because Zammad's tag list endpoint requires
     * `object` (the object type name, e.g. `'Ticket'`) and `o_id` (the object's
     * numeric ID) to scope the result. Without these parameters the API returns
     * an empty list. Defaults to `object=Ticket, o_id=1` when not provided in $query.
     *
     * @param array<string, mixed> $query May include `'object'` (string) and `'o_id'` (int or string).
     * @return Generator<int, TagDTO>
     */
    public function all(array $query = []): iterable
    {
        $object = $query['object'] ?? 'Ticket';
        $oId = $query['o_id'] ?? '1';

        $page = 1;

        do {
            $params = array_merge($query, [
                'page' => (string) $page,
                'per_page' => (string) $this->pageSize,
                'object' => $object,
                'o_id' => $oId,
            ]);
            $items = $this->extractItems($this->handler->get('tags', $params));

            foreach ($items as $item) {
                yield TagDTO::fromArray($item);
            }

            $hasMore = count($items) === $this->pageSize;
            $page++;
        } while ($hasMore);
    }

    /**
     * Searches tags globally by prefix and yields matching TagDTOs.
     *
     * Redirects to the `/tag_search` autocomplete endpoint (via
     * {@see self::tagSearch()}) instead of the standard search path, because
     * Zammad's tag endpoint does not support generic full-text search. The
     * raw response from `tagSearch` is an array of name strings; this method
     * wraps each as a `TagDTO` for a consistent iterable API.
     *
     * @param array<string, mixed> $query Unused (tag search has no extra params).
     * @return Generator<int, TagDTO>
     */
    public function search(string $term, array $query = []): iterable
    {
        foreach ($this->tagSearch($term) as $item) {
            if (is_array($item)) {
                /** @var array<string, mixed> $item */
                yield TagDTO::fromArray($item);
            }
        }
    }

    /**
     * Attaches a tag to a specific Zammad object.
     *
     * Sends a POST to `/tags/add`. If the tag does not exist in Zammad's tag
     * list yet, Zammad creates it automatically. The response contains the
     * updated tag state for the object.
     *
     * @param string $objectType Zammad object class name (e.g. `'Ticket'`).
     * @param int    $objectId   Numeric ID of the object to tag.
     * @param string $tag        Tag label to attach (case-insensitive in Zammad).
     * @return array<string, mixed>
     */
    public function add(
        string $objectType,
        int $objectId,
        string $tag,
    ): array {
        return $this->handler->post('tags/add', [
            'object' => $objectType,
            'o_id' => $objectId,
            'item' => $tag,
        ]);
    }

    /**
     * Detaches a tag from a specific Zammad object.
     *
     * Sends a DELETE to `/tags/remove` with the object context in the query
     * string. The $tag value is URL-encoded to handle special characters safely.
     * Removing a tag that is not attached to the object is a no-op on the server.
     *
     * @param string $objectType Zammad object class name (e.g. `'Ticket'`).
     * @param int    $objectId   Numeric ID of the object to untag.
     * @param string $tag        Tag label to detach.
     * @return array<string, mixed>
     */
    public function remove(
        string $objectType,
        int $objectId,
        string $tag,
    ): array {
        $uri = "tags/remove?object={$objectType}&o_id={$objectId}&item=" . urlencode($tag);

        return $this->handler->delete($uri);
    }

    /**
     * Autocomplete search across all tags in the Zammad instance.
     *
     * Calls the dedicated `/tag_search` endpoint, which returns tag names
     * matching the $term prefix. This endpoint is separate from the tag list
     * because it searches across all taggable objects, not just a specific one.
     *
     * The raw response format is `[{"id": 1, "value": "bug"}, ...]`; callers
     * who need typed DTOs should use {@see self::search()} instead.
     *
     * @return array<string, mixed> Raw tag search result from the API.
     */
    public function tagSearch(string $term): array
    {
        return $this->handler->get('tag_search', ['term' => $term]);
    }
}
