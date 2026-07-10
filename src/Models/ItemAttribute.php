<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class ItemAttribute
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $value = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            name: Wire::str($w, 'name'),
            value: Wire::str($w, 'value'),
        );
    }
}
