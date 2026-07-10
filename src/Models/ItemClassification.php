<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class ItemClassification
{
    public function __construct(
        public readonly ?string $code = null,
        /** UNTDID 7143 scheme identifier. */
        public readonly ?string $listId = null,
        public readonly ?string $listVersionId = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            code: Wire::str($w, 'code'),
            listId: Wire::str($w, 'listId'),
            listVersionId: Wire::str($w, 'listVersionId'),
        );
    }
}
