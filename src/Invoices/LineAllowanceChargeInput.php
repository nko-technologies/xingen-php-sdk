<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Line-level allowance or charge (BG-27/BG-28). */
final class LineAllowanceChargeInput
{
    /** $amount/$baseAmount/$percentage are strings, not floats -- see {@see LineInput} for why
     * monetary fields are kept as strings end to end. */
    public function __construct(
        /** true = charge, false = allowance. */
        public readonly bool $charge,
        public readonly string $amount,
        public readonly ?string $baseAmount = null,
        public readonly ?string $percentage = null,
        public readonly ?string $reason = null,
        public readonly ?string $reasonCode = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'charge' => $this->charge,
            'amount' => $this->amount,
            'baseAmount' => $this->baseAmount,
            'percentage' => $this->percentage,
            'reason' => $this->reason,
            'reasonCode' => $this->reasonCode,
        ];
    }
}
