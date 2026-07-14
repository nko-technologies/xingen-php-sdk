<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Invoicing period (BG-14 document-level / BG-26 line-level). */
final class InvoicePeriodInput
{
    public function __construct(
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        /** Tax point date code, UNTDID 2005 (BT-8, document level only). */
        public readonly ?string $descriptionCode = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'descriptionCode' => $this->descriptionCode,
        ];
    }
}
