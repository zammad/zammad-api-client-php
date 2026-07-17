<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

use JsonSerializable;

/**
 * Marks a class as an immutable API data-transfer object.
 *
 * DTOs carry structured data between the HTTP layer and the application.
 * They are intentionally read-only: all properties are set during construction
 * and must not be mutated afterwards. Implementing classes should use PHP 8.1+
 * promoted readonly constructor parameters so that the constructor itself
 * serves as the authoritative schema of the API resource.
 *
 * Hydration from raw API responses is provided via {@see self::fromArray()}.
 * Serialization back to the wire format is provided via {@see self::toArray()}
 * and {@see self::jsonSerialize()}.
 *
 * The {@see \ZammadAPIClient\Core\Traits\HydratesFromArray} and
 * {@see \ZammadAPIClient\Core\Traits\SerializesToArray} traits supply
 * default reflection-based implementations; override only when the API
 * shape does not map 1:1 to the constructor parameter names.
 */
interface DTOInterface extends JsonSerializable
{
    /**
     * Constructs a DTO from a raw API response array.
     *
     * Field names in $data are matched to constructor parameter names using
     * reflection (via {@see \ZammadAPIClient\Core\DtoHydrator}). Unknown keys
     * are silently ignored; missing keys resolve to the parameter's default
     * value or null for nullable parameters.
     *
     * @param array<string, mixed> $data Raw JSON-decoded response from the API.
     * @return static
     */
    public static function fromArray(array $data): static;

    /**
     * Serializes the DTO to an associative array suitable for API requests.
     *
     * Keys match the API field names. Null values are omitted from the result;
     * use a dedicated patch DTO
     * (e.g. {@see \ZammadAPIClient\Endpoints\Tickets\TicketUpdateDTO})
     * when you need to explicitly send null to the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Returns the server-assigned ID, or null before the object is persisted.
     *
     * The ID is assigned by Zammad on creation and is never set by the client.
     * A null return value signals that the DTO represents an unsaved resource.
     */
    public function id(): ?int;

    /**
     * Alias of {@see self::toArray()} required by JsonSerializable.
     *
     * Enables direct JSON encoding: `json_encode($dto)` produces the same
     * output as `json_encode($dto->toArray())`.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array;
}
