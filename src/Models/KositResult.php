<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class KositResult
{
    /** @param list<string> $errors */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            valid: Wire::bool($w, 'valid'),
            errors: Wire::strArr($w, 'errors'),
        );
    }
}
