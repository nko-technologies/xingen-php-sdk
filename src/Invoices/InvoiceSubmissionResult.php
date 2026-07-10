<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

use Xingen\Sdk\Internal\Wire;
use Xingen\Sdk\Models\InvoiceStatus;

/** The 202 Accepted envelope every submit/validate endpoint returns. */
final class InvoiceSubmissionResult
{
    public function __construct(
        public readonly string $id,
        public readonly InvoiceStatus $status,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id') ?? '',
            status: InvoiceStatus::from(Wire::str($w, 'status') ?? ''),
        );
    }
}
