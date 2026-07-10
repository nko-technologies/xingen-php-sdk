<?php

declare(strict_types=1);

namespace Xingen\Sdk\Internal;

/**
 * Small type-safe accessors for hydrating typed models out of the array tree produced by
 * {@see Json::decode}. Centralizes the `$raw['foo'] ?? null` / is_array() checks that would
 * otherwise be repeated by hand in every model's fromWire() method.
 */
final class Wire
{
    private function __construct()
    {
    }

    /** @param array<string, mixed>|mixed $raw
     * @return array<string, mixed> */
    public static function asWire(mixed $raw): array
    {
        return is_array($raw) ? $raw : [];
    }

    /** @param array<string, mixed> $raw */
    public static function str(array $raw, string $key): ?string
    {
        $value = $raw[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @param array<string, mixed> $raw */
    public static function bool(array $raw, string $key, bool $default = false): bool
    {
        $value = $raw[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /** Narrows a stringified numeric token (see {@see Json::decode}) back to an int. Only
     * ever used for the handful of known-safe small integer counters -- every
     * monetary/quantity field stays a string via {@see self::str()} instead.
     * @param array<string, mixed> $raw */
    public static function int(array $raw, string $key, int $default = 0): int
    {
        $value = $raw[$key] ?? null;

        return (is_string($value) || is_int($value)) ? (int) $value : $default;
    }

    /** Same as {@see self::int()}, but for fields that are nullable (e.g. an unlimited
     * quota), returning null when the field is absent or not numeric rather than falling
     * back to 0.
     * @param array<string, mixed> $raw */
    public static function intOrNull(array $raw, string $key): ?int
    {
        $value = $raw[$key] ?? null;

        return (is_string($value) || is_int($value)) ? (int) $value : null;
    }

    /** @param array<string, mixed> $raw
     * @template T
     * @param callable(mixed): T $fromWire
     * @return T|null */
    public static function obj(array $raw, string $key, callable $fromWire): mixed
    {
        $value = $raw[$key] ?? null;

        return is_array($value) ? $fromWire($value) : null;
    }

    /** @param array<string, mixed> $raw
     * @template T
     * @param callable(mixed): T $fromWire
     * @return list<T> */
    public static function arr(array $raw, string $key, callable $fromWire): array
    {
        $value = $raw[$key] ?? null;

        return is_array($value) ? array_values(array_map($fromWire, $value)) : [];
    }

    /** @param array<string, mixed> $raw
     * @return list<string> */
    public static function strArr(array $raw, string $key): array
    {
        $value = $raw[$key] ?? null;
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item)));
    }

    /** @param array<string, mixed> $raw
     * @return array<string, string>|null */
    public static function stringMap(array $raw, string $key): ?array
    {
        $value = $raw[$key] ?? null;
        if (!is_array($value)) {
            return null;
        }
        $result = [];
        foreach ($value as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $result[$k] = $v;
            }
        }

        return $result;
    }
}
