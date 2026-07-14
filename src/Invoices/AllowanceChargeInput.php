<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Document-level allowance or charge (BG-20/BG-21). */
final class AllowanceChargeInput
{
    /** $amount/$baseAmount/$percentage/$vatRate are strings, not floats -- see {@see LineInput}
     * for why monetary fields are kept as strings end to end. */
    public function __construct(
        /** true = charge (BG-21), false = allowance (BG-20). */
        public readonly bool $charge,
        public readonly string $amount,
        public readonly ?string $baseAmount = null,
        public readonly ?string $percentage = null,
        public readonly ?string $vatCategoryCode = null,
        public readonly ?string $vatRate = null,
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
            'vatCategoryCode' => $this->vatCategoryCode,
            'vatRate' => $this->vatRate,
            'reason' => $this->reason,
            'reasonCode' => $this->reasonCode,
        ];
    }
}
