<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class ValidationResult
{
    /** @param list<ValidationError> $errors */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
        /** Only populated for XML-based validation paths (UBL/CII/IDoc). null otherwise. */
        public readonly ?KositResult $kositResult = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            valid: Wire::bool($w, 'valid'),
            errors: Wire::arr($w, 'errors', ValidationError::fromWire(...)),
            kositResult: Wire::obj($w, 'kositResult', KositResult::fromWire(...)),
        );
    }
}
