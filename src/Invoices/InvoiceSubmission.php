<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

use Xingen\Sdk\Models\ValidationProfile;

/** Request body for submitting a structured (JSON) invoice. */
final class InvoiceSubmission
{
    /** @param list<LineInput> $lines */
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly string $issueDate,
        public readonly string $currency,
        public readonly ValidationProfile $validationProfile,
        public readonly PartyInput $supplier,
        public readonly PartyInput $buyer,
        public readonly ?string $buyerReference = null,
        public readonly array $lines = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'invoiceNumber' => $this->invoiceNumber,
            'issueDate' => $this->issueDate,
            'currency' => $this->currency,
            'buyerReference' => $this->buyerReference,
            'validationProfile' => $this->validationProfile->value,
            'supplier' => $this->supplier->toArray(),
            'buyer' => $this->buyer->toArray(),
            'lines' => array_map(static fn (LineInput $line): array => $line->toArray(), $this->lines),
        ];
    }
}
