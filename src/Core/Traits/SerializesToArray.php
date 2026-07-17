<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Traits;

use DateTimeImmutable;

/**
 * Provides standard DTO serialisation for classes with promoted readonly properties.
 *
 * Consuming DTOs must declare a public nullable `$id` property so that
 * {@see self::id()} can return it. All other public properties are included
 * in {@see self::toArray()} automatically via `get_object_vars()`.
 *
 * Serialisation rules:
 *  - Null values are omitted so the API only receives explicitly set fields.
 *  - DateTimeImmutable values are formatted as ISO 8601 strings (`c` format).
 *  - All other scalar values are included as-is.
 *  - Zammad server-managed read-only keys are filtered from `customFields`
 *    to prevent them from being sent back on update requests (e.g. `article_count`,
 *    `preferences`, `created_by_id`).
 *
 * Pair with {@see \ZammadAPIClient\Core\Traits\HydratesFromArray} for a
 * complete, zero-boilerplate DTO implementation.
 */
trait SerializesToArray
{
    /**
     * Zammad API response keys that are server-managed and must never be
     * written back to the API. These keys would otherwise leak through
     * `customFields` (which catches all unknown response keys) and pollute
     * update payloads.
     *
     * This is a best-effort denylist. Zammad silently ignores unknown or
     * read-only fields in update requests, so missed keys are functionally
     * harmless — the list exists to keep payloads clean, not to prevent errors.
     * Some keys are resource-specific (e.g. ticket-only fields) and never
     * appear in User/Group/Organization responses; filtering them there is a
     * harmless no-op.
     *
     * @return list<string>
     */
    private static function serverReadOnlyKeys(): array
    {
        return [
            'article_ids',
            'article_count',
            'checklist_id',
            'close_at',
            'create_article_sender_id',
            'create_article_type_id',
            'created_by',
            'created_by_id',
            'escalation_at',
            'first_response_at',
            'last_contact_agent_at',
            'last_contact_at',
            'last_contact_customer_at',
            'last_owner_update_at',
            'pending_close_at',
            'pending_reminder_at',
            'preferences',
            'referencing_checklist_ids',
            'ticket_time_accounting_ids',
            'updated_by',
            'updated_by_id',
        ];
    }

    /**
     * Serialises all non-null public properties to an associative array.
     *
     * The resulting array is suitable for API request bodies. DateTimeImmutable
     * values are converted to ISO 8601 strings; all other values are passed
     * through. Null properties are excluded so partial DTO construction does
     * not send empty fields to the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ($value !== null && $key !== 'customFields') {
                $result[$key] = $value instanceof DateTimeImmutable ? $value->format('c') : $value;
            }
        }

        if (array_key_exists('customFields', $vars) && is_array($vars['customFields'])) {
            foreach ($vars['customFields'] as $k => $v) {
                if (!in_array($k, self::serverReadOnlyKeys(), true)) {
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the server-assigned resource ID, or null for unsaved DTOs.
     *
     * The consuming class must declare `public readonly ?int $id = null`.
     * This method reads it directly; no property access indirection is used.
     *
     * @deprecated Use the `$dto->id` property directly. The method will be
     *             removed in v4.0 alongside the DTOInterface contract change.
     */
    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * Delegates to {@see self::toArray()} to satisfy the JsonSerializable contract.
     *
     * Allows `json_encode($dto)` to produce the same output as
     * `json_encode($dto->toArray())` without any extra code in the DTO.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
