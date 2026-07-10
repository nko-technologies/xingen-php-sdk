<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class PartyIdentifier
{
    public function __construct(
        public readonly ?string $id = null,
        /** ISO 6523 ICD, or "SEPA" for creditor identifiers. */
        public readonly ?string $schemeId = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id'),
            schemeId: Wire::str($w, 'schemeId'),
        );
    }
}
