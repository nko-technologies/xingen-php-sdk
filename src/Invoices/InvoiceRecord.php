<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

use Xingen\Sdk\Internal\Wire;
use Xingen\Sdk\Models\Invoice;
use Xingen\Sdk\Models\InvoiceStatus;
use Xingen\Sdk\Models\ValidationResult;

final class InvoiceRecord
{
    public function __construct(
        public readonly string $id,
        public readonly InvoiceStatus $status,
        /** null while status is PROCESSING. */
        public readonly ?Invoice $invoice,
        /** null while status is PROCESSING. */
        public readonly ?ValidationResult $validationResult,
        public readonly string $createdAt,
        public readonly string $validationProfile,
        public readonly string $invoiceFormat,
        public readonly string $uploadedBy,
        public readonly bool $sandbox = false,
        public readonly ?string $apiKeyId = null,
        /** Extraction quality tier used ("FAST"/"ACCURATE") -- only set for AI PDF extractions
         * ({@see self::$invoiceFormat} === "PDF_AI"). */
        public readonly ?string $extractionTier = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id') ?? '',
            status: InvoiceStatus::from(Wire::str($w, 'status') ?? ''),
            invoice: Wire::obj($w, 'canonicalJson', Invoice::fromWire(...)),
            validationResult: Wire::obj($w, 'validationResult', ValidationResult::fromWire(...)),
            createdAt: Wire::str($w, 'createdAt') ?? '',
            validationProfile: Wire::str($w, 'validationProfile') ?? '',
            invoiceFormat: Wire::str($w, 'invoiceFormat') ?? '',
            uploadedBy: Wire::str($w, 'uploadedBy') ?? '',
            sandbox: Wire::bool($w, 'sandbox'),
            apiKeyId: Wire::str($w, 'apiKeyId'),
            extractionTier: Wire::str($w, 'extractionTier'),
        );
    }
}
