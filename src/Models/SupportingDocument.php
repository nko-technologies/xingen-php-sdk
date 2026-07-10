<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class SupportingDocument
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $schemeId = null,
        /** UNTDID 1001 document type code, e.g. "50", "130". */
        public readonly ?string $typeCode = null,
        public readonly ?string $description = null,
        /** null = no external-reference element; empty string = present but the URI is missing. */
        public readonly ?string $externalUri = null,
        public readonly ?string $mimeCode = null,
        public readonly ?string $filename = null,
        public readonly bool $embeddedPresent = false,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id'),
            schemeId: Wire::str($w, 'schemeId'),
            typeCode: Wire::str($w, 'typeCode'),
            description: Wire::str($w, 'description'),
            externalUri: Wire::str($w, 'externalUri'),
            mimeCode: Wire::str($w, 'mimeCode'),
            filename: Wire::str($w, 'filename'),
            embeddedPresent: Wire::bool($w, 'embeddedPresent'),
        );
    }
}
