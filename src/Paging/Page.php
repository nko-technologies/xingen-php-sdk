<?php

declare(strict_types=1);

namespace Xingen\Sdk\Paging;

use Xingen\Sdk\Internal\Wire;

/**
 * Mirrors Spring Data's `Page<T>` JSON shape. Extra fields present on the real payload
 * (`pageable`, `sort`, ...) are simply not read here.
 *
 * PHP has no generics, so `$content` is typed `array` rather than `list<T>`; callers pass
 * the item type's `fromWire` callable to {@see self::fromWire()}.
 */
final class Page
{
    /** @param list<mixed> $content */
    public function __construct(
        public readonly array $content,
        public readonly int $totalElements,
        public readonly int $totalPages,
        public readonly int $number,
        public readonly int $size,
        public readonly bool $first,
        public readonly bool $last,
        public readonly int $numberOfElements,
        public readonly bool $empty,
    ) {
    }

    /** @param callable(mixed): mixed $itemFromWire */
    public static function fromWire(mixed $raw, callable $itemFromWire): self
    {
        $w = Wire::asWire($raw);

        return new self(
            content: Wire::arr($w, 'content', $itemFromWire),
            totalElements: Wire::int($w, 'totalElements'),
            totalPages: Wire::int($w, 'totalPages'),
            number: Wire::int($w, 'number'),
            size: Wire::int($w, 'size'),
            first: Wire::bool($w, 'first'),
            last: Wire::bool($w, 'last'),
            numberOfElements: Wire::int($w, 'numberOfElements'),
            empty: Wire::bool($w, 'empty'),
        );
    }
}
