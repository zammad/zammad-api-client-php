<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Tags;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a single tag assignment on a Zammad object (`/api/v1/tags`).
 *
 * Tags in Zammad are not global labels by themselves; they exist as associations
 * between a tag name (`value`) and a specific taggable object (identified by
 * its type name `object` and numeric ID `o_id`).
 *
 * Field semantics:
 *  - `value`  — The human-readable tag string (e.g. `'bug'`, `'urgent'`).
 *  - `object` — Zammad object class name that the tag is attached to (e.g. `'Ticket'`).
 *  - `o_id`   — The numeric ID of the specific object instance being tagged.
 *
 * All fields are nullable because the `/tag_search` autocomplete endpoint returns
 * only `id` and `value` without `object` or `o_id`.
 *
 * Note: The `id` field here is the tag-assignment ID, not the tag label's ID in
 * the Zammad tag list. Two identical tag strings on different tickets have
 * different assignment IDs.
 */
final readonly class TagDTO implements DTOInterface
{
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public ?int $id,
        public ?string $object,
        public ?int $o_id,
        public ?string $value,
    ) {
    }
}
