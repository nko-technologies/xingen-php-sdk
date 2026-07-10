<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

/** Document-level allowance/charge (BG-20/BG-21). */
final class AllowanceCharge
{
    public function __construct(
        /** true = charge, false = allowance. */
        public readonly bool $charge,
        public readonly ?string $amount = null,
        public readonly ?string $baseAmount = null,
        public readonly ?string $percentage = null,
        public readonly ?string $vatCategoryCode = null,
        public readonly ?string $vatRate = null,
        public readonly ?string $reason = null,
        public readonly ?string $reasonCode = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            charge: Wire::bool($w, 'charge'),
            amount: Wire::str($w, 'amount'),
            baseAmount: Wire::str($w, 'baseAmount'),
            percentage: Wire::str($w, 'percentage'),
            vatCategoryCode: Wire::str($w, 'vatCategoryCode'),
            vatRate: Wire::str($w, 'vatRate'),
            reason: Wire::str($w, 'reason'),
            reasonCode: Wire::str($w, 'reasonCode'),
        );
    }
}
