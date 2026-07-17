<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Links;

use InvalidArgumentException;
use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/links` endpoint.
 *
 * Links connect two Zammad objects (most commonly tickets) together with a
 * relationship type (`normal`, `parent`, or `child`). Unlike most API endpoints,
 * links are not retrieved by a numeric `find()` call but rather by providing
 * the source object type and ID as query parameters.
 *
 * Standard CRUD methods from {@see AbstractRepository} are supported where
 * applicable; {@see self::add()} and {@see self::remove()} provide dedicated
 * convenience methods for the `/links/add` and `/links/remove` endpoints.
 *
 * @extends AbstractRepository<LinkDTO>
 */
final class LinkRepository extends AbstractRepository
{
    protected function getListKey(): string
    {
        return 'links';
    }

    /**
     * Lists all links for a given Zammad object.
     *
     * The Zammad link API requires `link_object` (the object type name,
     * e.g. `'Ticket'`) and `link_object_value` (the object's numeric ID)
     * as query parameters. Without them the API returns no data.
     *
     * @param array<string, mixed> $query Must include `'object'` (string) and `'object_id'` (int).
     * @return \Generator<int, LinkDTO>
     */
    public function all(array $query = []): iterable
    {
        $object = $query['object']
            ?? throw new InvalidArgumentException('The "object" key (e.g. "Ticket") is required in $query.');
        $objectId = $query['object_id']
            ?? throw new InvalidArgumentException('The "object_id" key (the object ID) is required in $query.');

        return $this->paginate($this->resourcePath, [
            'link_object'       => $object,
            'link_object_value' => $objectId,
        ]);
    }

    /**
     * Creates a link between two objects.
     *
     * Sends a POST to `/links/add`. The link type must be one of
     * `'normal'`, `'parent'`, or `'child'`.
     *
     * The source is identified by its ticket *number* (e.g. `'84001'`),
     * the target by its numeric ID. This matches Zammad's API contract:
     * the source is looked up via `Ticket.find_by(number:)`, the target
     * via `Ticket.find(id:)`.
     *
     * @param string $linkType     Link type (`'normal'`, `'parent'`, or `'child'`).
     * @param string $sourceType   Source object type (e.g. `'Ticket'`).
     * @param string $sourceNumber Source ticket number (e.g. `'84001'`), not the ID.
     * @param string $targetType   Target object type (e.g. `'Ticket'`).
     * @param int    $targetId     Target object ID.
     * @return array<string, mixed>
     */
    public function add(
        string $linkType,
        string $sourceType,
        string $sourceNumber,
        string $targetType,
        int $targetId,
    ): array {
        return $this->handler->post('links/add', [
            'link_type'                  => $linkType,
            'link_object_source'         => $sourceType,
            'link_object_source_number'  => $sourceNumber,
            'link_object_target'         => $targetType,
            'link_object_target_value'   => $targetId,
        ]);
    }

    /**
     * Removes a link between two objects.
     *
     * Sends a DELETE to `/links/remove`. The source is identified by its
     * internal database ID (not the ticket number), matching the Zammad API
     * requirement for `link_object_source_value`. The target is identified
     * by its numeric ID via `link_object_target_value`.
     *
     * @param string $linkType   Link type.
     * @param string $sourceType Source object type.
     * @param int    $sourceId   Source object internal database ID.
     * @param string $targetType Target object type.
     * @param int    $targetId   Target object ID.
     * @return array<string, mixed>
     */
    public function remove(
        string $linkType,
        string $sourceType,
        int $sourceId,
        string $targetType,
        int $targetId,
    ): array {
        $uri = 'links/remove?' . http_build_query([
            'link_type'                  => $linkType,
            'link_object_source'         => $sourceType,
            'link_object_source_value'   => $sourceId,
            'link_object_target'         => $targetType,
            'link_object_target_value'   => $targetId,
        ]);

        return $this->handler->delete($uri);
    }
}
