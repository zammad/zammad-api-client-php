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
 *
 * Pair with {@see \ZammadAPIClient\Core\Traits\HydratesFromArray} for a
 * complete, zero-boilerplate DTO implementation.
 */
trait SerializesToArray
{
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
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value instanceof DateTimeImmutable ? $value->format('c') : $value;
            }
        }

        return $result;
    }

    /**
     * Returns the server-assigned resource ID, or null for unsaved DTOs.
     *
     * The consuming class must declare `public readonly ?int $id = null`.
     * This method reads it directly; no property access indirection is used.
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
