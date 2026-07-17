<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;

/**
 * Stateful resource wrapper — Ruby-style mutable resource with changes tracking.
 *
 * Wraps an immutable DTO and provides property-level mutation with
 * automatic change tracking. Only changed fields are sent on save.
 *
 * Usage:
 *   $resource = $client->ticket()->resource(1);
 *   $resource->title = 'New Title';     // tracked in changes
 *   $resource->state_id = 3;            // tracked in changes
 *   $resource->save();                  // PUT only {title, state_id}
 */
final class Resource
{
    /** @var array<string, mixed> */
    private array $attributes;

    /** @var array<string, array{old: mixed, new: mixed}> */
    private array $changes = [];

    private bool $newRecord;

    /**
     * @param DTOInterface            $dto     Underlying immutable DTO (the source of truth).
     * @param RequestHandlerInterface $handler For API calls (save, destroy).
     * @param string                  $path    API path (e.g. 'tickets').
     */
    public function __construct(
        private DTOInterface $dto,
        private RequestHandlerInterface $handler,
        private string $path,
    ) {
        $this->attributes = $dto->toArray();
        $this->newRecord = $dto->id() === null;
    }

    /**
     * Returns a property value from the current resource state.
     *
     * Values are stored as serialized array data (strings for dates, scalars
     * for primitives). Use {@see self::toDTO()} to access typed DTO properties
     * (e.g. DateTimeImmutable for timestamps).
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $old = $this->attributes[$name] ?? null;
        $this->attributes[$name] = $value;
        $this->changes[$name] = ['old' => $old, 'new' => $value];
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function id(): ?int
    {
        $value = $this->attributes['id'] ?? null;

        return is_scalar($value) ? (int) $value : null;
    }

    public function newRecord(): bool
    {
        return $this->newRecord;
    }

    public function changed(): bool
    {
        return !empty($this->changes);
    }

    /** @return array<string, array{old: mixed, new: mixed}> */
    public function changes(): array
    {
        return $this->changes;
    }

    /**
     * Keys that are never sent to the API because they are server-assigned.
     */
    private const READ_ONLY_KEYS = ['id', 'created_at', 'updated_at'];

    /**
     * Persists the resource to the Zammad API.
     *
     * - New record: POST to create.
     * - Existing record with changes: PUT only changed fields.
     * - Existing record without changes: no request.
     *
     * @throws \ZammadAPIClient\Exceptions\AuthenticationException
     * @throws \ZammadAPIClient\Exceptions\ValidationException
     * @throws \ZammadAPIClient\Exceptions\NetworkException
     *
     * @return $this
     */
    public function save(): self
    {
        if ($this->newRecord) {
            $payload = array_diff_key($this->attributes, array_flip(self::READ_ONLY_KEYS));
            $data = $this->handler->post($this->path, $payload);
        } elseif ($this->changed()) {
            $diff = [];
            foreach ($this->changes as $field => $change) {
                $diff[$field] = $change['new'];
            }
            $data = $this->handler->put("{$this->path}/{$this->id()}", $diff);
        } else {
            return $this;
        }

        $this->attributes = array_merge($this->attributes, $data);
        $this->dto = $this->dto::fromArray($this->attributes);
        $this->changes = [];
        $this->newRecord = false;

        return $this;
    }

    /**
     * Deletes the resource via DELETE request.
     */
    public function destroy(): void
    {
        if ($this->newRecord) {
            throw new \RuntimeException('Cannot destroy a new record.');
        }

        $this->handler->delete("{$this->path}/{$this->id()}");
    }

    /**
     * Returns the underlying DTO (rebuilds from current attributes).
     *
     * @return DTOInterface
     */
    public function toDTO(): DTOInterface
    {
        $class = get_class($this->dto);

        return $class::fromArray($this->attributes);
    }
}
