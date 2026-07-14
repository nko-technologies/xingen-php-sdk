<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

/** A field the backend fills in automatically when it isn't supplied, e.g. from
 * `GET /v1/invoices/auto-filled-fields`. */
final class AutoFilledField
{
    public function __construct(
        /** Canonical Invoice field path, e.g. "typeCode" or "lines[].lineId". */
        public readonly string $field,
        /** The value that will be set, or a short description when it isn't a fixed value. */
        public readonly string $value,
        /** Why it's set automatically, in user-facing language. */
        public readonly string $reason,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            field: Wire::str($w, 'field') ?? '',
            value: Wire::str($w, 'value') ?? '',
            reason: Wire::str($w, 'reason') ?? '',
        );
    }
}
