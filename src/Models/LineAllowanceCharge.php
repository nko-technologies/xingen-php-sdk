<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

/** Line-level allowance/charge (BT-136..BT-141). */
final class LineAllowanceCharge
{
    public function __construct(
        public readonly bool $charge,
        public readonly ?string $amount = null,
        public readonly ?string $baseAmount = null,
        public readonly ?string $percentage = null,
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
            reason: Wire::str($w, 'reason'),
            reasonCode: Wire::str($w, 'reasonCode'),
        );
    }
}
