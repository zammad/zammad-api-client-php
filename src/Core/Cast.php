<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use DateTimeImmutable;
use DateMalformedStringException;

/**
 * Stateless scalar coercions for hydrating DTOs from lenient API payloads.
 *
 * Zammad's API occasionally returns numeric IDs as strings, booleans as 0/1,
 * or timestamps as empty strings. This class normalises those edge cases so
 * DTO constructors receive correctly-typed values without each DTO having to
 * repeat defensive casts.
 *
 * All methods are pure static functions: they read from $data by key and return
 * a typed value or null. They never throw — a missing or uncastable value
 * always results in null or the supplied default.
 *
 * Used exclusively by {@see \ZammadAPIClient\Core\DtoHydrator}.
 */
final class Cast
{
    /**
     * Extracts a key from $data and parses it as a DateTimeImmutable.
     *
     * Returns null if the key is missing, empty, or not a valid ISO 8601 date
     * string. This guards against Zammad occasionally returning `""` for
     * unset timestamp fields.
     *
     * @param array<string, mixed> $data
     */
    public static function dateTime(array $data, string $key): ?DateTimeImmutable
    {
        $value = $data[$key] ?? null;

        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (DateMalformedStringException) {
            return null;
        }
    }

    /**
     * Extracts a key from $data as a string, falling back to $default.
     *
     * Non-string values (e.g. integers returned by the API in unexpected fields)
     * are ignored and the default is returned instead of casting silently.
     *
     * @param array<string, mixed> $data
     */
    public static function string(
        array $data,
        string $key,
        string $default = '',
    ): string {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * Extracts a key from $data as a string, or null if absent or not a string.
     *
     * @param array<string, mixed> $data
     */
    public static function stringOrNull(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Extracts a key from $data as an int, or null if absent.
     *
     * Any scalar value (string, float, bool) is cast via `(int)` to handle
     * Zammad occasionally returning IDs as numeric strings (e.g. `"42"`).
     * Non-scalar values (arrays, objects) return null.
     *
     * @param array<string, mixed> $data
     */
    public static function intOrNull(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (int) $value : null;
    }

    /**
     * Extracts a key from $data as a bool, or null if the key is absent.
     *
     * A present value of 0 or false casts to `false` (not null), so callers
     * can distinguish "field explicitly set to false" from "field not present".
     *
     * @param array<string, mixed> $data
     */
    public static function boolOrNull(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (bool) $value;
    }
}
