<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class TaxBreakdown
{
    public function __construct(
        public readonly ?string $taxableAmount = null,
        public readonly ?string $taxAmount = null,
        /** S / Z / E / AE / K / G / O */
        public readonly ?string $categoryCode = null,
        /** null for exempt categories (E/AE/K/G/O). */
        public readonly ?string $rate = null,
        public readonly ?string $exemptionReason = null,
        public readonly ?string $exemptionReasonCode = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            taxableAmount: Wire::str($w, 'taxableAmount'),
            taxAmount: Wire::str($w, 'taxAmount'),
            categoryCode: Wire::str($w, 'categoryCode'),
            rate: Wire::str($w, 'rate'),
            exemptionReason: Wire::str($w, 'exemptionReason'),
            exemptionReasonCode: Wire::str($w, 'exemptionReasonCode'),
        );
    }
}
