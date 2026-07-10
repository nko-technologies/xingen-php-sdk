<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

/** Invoicing period, at document level (BG-14) or line level (BG-26). */
final class InvoicePeriod
{
    public function __construct(
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        /** Document level only (UNTDID 2005 tax point date code). */
        public readonly ?string $descriptionCode = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            startDate: Wire::str($w, 'startDate'),
            endDate: Wire::str($w, 'endDate'),
            descriptionCode: Wire::str($w, 'descriptionCode'),
        );
    }
}
