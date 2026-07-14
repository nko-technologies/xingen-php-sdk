<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Preceding invoice reference (BG-3) -- e.g. the original invoice a credit note corrects. */
final class PrecedingInvoiceReferenceInput
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $issueDate = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'issueDate' => $this->issueDate,
        ];
    }
}
