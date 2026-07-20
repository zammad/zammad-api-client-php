<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Repository;

use DateTimeImmutable;
use ReflectionClass;
use ReflectionNamedType;
use ZammadAPIClient\Core\Cast;

/**
 * Type-driven DTO hydration: maps array keys onto constructor parameters using
 * each parameter's declared type. The constructor is the single source of
 * truth; scalar coercion is delegated to {@see Cast}.
 */
final class DtoHydrator
{
    /**
     * Cached constructor metadata per class: ordered list of name/type/nullable.
     *
     * @var array<class-string, list<array{name: string, type: ?string, nullable: bool}>>
     */
    private static array $metaCache = [];

    /**
     * Instantiates $class by mapping $data array keys to constructor parameters.
     *
     * The constructor is introspected once per class and the result is cached
     * in {@see self::$metaCache} to avoid repeated reflection calls. Each
     * parameter's declared type drives the coercion applied via {@see Cast}:
     * e.g. a `?DateTimeImmutable` parameter gets `Cast::dateTime()`, a
     * `string` parameter gets `Cast::string()`, etc. Parameters for which no
     * key exists in $data receive null (nullable) or a zero-value (non-nullable).
     *
     * @template T of object
     * @param class-string<T> $class Fully-qualified DTO class to instantiate.
     * @param array<string, mixed> $data Raw API response fields.
     * @return T
     */
    public static function hydrate(string $class, array $data): object
    {
        $args = [];
        $known = [];
        $customFieldsIndex = null;

        foreach (self::constructorMeta($class) as $i => $param) {
            $known[] = $param['name'];
            if ($param['name'] === 'customFields') {
                $customFieldsIndex = $i;
            }
            $args[] = self::coerce($param['type'], $param['nullable'], $data, $param['name']);
        }

        if ($customFieldsIndex !== null) {
            $args[$customFieldsIndex] = array_diff_key($data, array_flip($known));
        }

        return new $class(...$args);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function coerce(?string $type, bool $nullable, array $data, string $name): mixed
    {
        return match ($type) {
            DateTimeImmutable::class => Cast::dateTime($data, $name),
            'int' => $nullable
                ? Cast::intOrNull($data, $name)
                : self::requireInt($data, $name),
            'bool' => $nullable
                ? Cast::boolOrNull($data, $name)
                : self::requireBool($data, $name),
            'string' => $nullable ? Cast::stringOrNull($data, $name) : Cast::string($data, $name),
            default => $data[$name] ?? null,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function requireInt(array $data, string $name): int
    {
        if (!array_key_exists($name, $data)) {
            throw new \RuntimeException(
                "Required field \"{$name}\" is missing from API response data.",
            );
        }

        $value = $data[$name];

        if (!is_scalar($value)) {
            throw new \RuntimeException(
                "Required field \"{$name}\" must be scalar, got " . get_debug_type($value) . '.',
            );
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function requireBool(array $data, string $name): bool
    {
        if (!array_key_exists($name, $data)) {
            return false;
        }

        return (bool) $data[$name];
    }

    /**
     * @param class-string $class
     * @return list<array{name: string, type: ?string, nullable: bool}>
     */
    private static function constructorMeta(string $class): array
    {
        if (isset(self::$metaCache[$class])) {
            return self::$metaCache[$class];
        }

        $constructor = (new ReflectionClass($class))->getConstructor();
        $meta = [];

        foreach ($constructor?->getParameters() ?? [] as $param) {
            $type = $param->getType();
            $meta[] = [
                'name' => $param->getName(),
                'type' => $type instanceof ReflectionNamedType ? $type->getName() : null,
                'nullable' => $type === null || $type->allowsNull(),
            ];
        }

        return self::$metaCache[$class] = $meta;
    }
}
