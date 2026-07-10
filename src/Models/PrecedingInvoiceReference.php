<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class PrecedingInvoiceReference
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $issueDate = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id'),
            issueDate: Wire::str($w, 'issueDate'),
        );
    }
}
