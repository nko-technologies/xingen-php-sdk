<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

use Xingen\Sdk\Models\ValidationProfile;

/** Request body for submitting a structured (JSON) invoice. */
final class InvoiceSubmission
{
    /**
     * @param list<string> $notes
     * @param list<PrecedingInvoiceReferenceInput> $precedingInvoiceReferences
     * @param list<SupportingDocumentInput> $supportingDocuments
     * @param list<LineInput> $lines
     * @param list<PaymentMeansInput> $paymentMeans
     * @param list<AllowanceChargeInput> $allowanceCharges
     */
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly string $issueDate,
        public readonly string $currency,
        public readonly ValidationProfile $validationProfile,
        public readonly PartyInput $supplier,
        public readonly PartyInput $buyer,
        public readonly ?string $buyerReference = null,
        /** Payment due date (BT-9). Either this or paymentTermsNote is required whenever the
         * payable amount is positive. */
        public readonly ?string $dueDate = null,
        /** Value added tax point date (BT-7). */
        public readonly ?string $taxPointDate = null,
        /** VAT accounting currency code (BT-6), if different from currency. */
        public readonly ?string $taxCurrencyCode = null,
        /** Payment terms (BT-20). Either this or dueDate is required whenever the payable amount
         * is positive. */
        public readonly ?string $paymentTermsNote = null,
        public readonly ?string $orderReference = null,
        public readonly ?string $salesOrderReference = null,
        public readonly ?string $projectReference = null,
        public readonly ?string $contractReference = null,
        public readonly ?string $receivingAdviceReference = null,
        public readonly ?string $despatchAdviceReference = null,
        public readonly ?string $tenderOrLotReference = null,
        public readonly ?string $invoicedObjectId = null,
        public readonly ?string $invoicedObjectSchemeId = null,
        public readonly ?string $buyerAccountingReference = null,
        public readonly array $notes = [],
        public readonly array $precedingInvoiceReferences = [],
        public readonly array $supportingDocuments = [],
        public readonly ?string $deliveryPeriodStart = null,
        public readonly ?string $deliveryPeriodEnd = null,
        public readonly ?InvoicePeriodInput $invoicePeriod = null,
        public readonly ?DeliveryInput $delivery = null,
        /** Payee, if different from the seller (BG-10). */
        public readonly ?PartyInput $payee = null,
        /** Seller's tax representative (BG-11). */
        public readonly ?PartyInput $taxRepresentative = null,
        public readonly array $lines = [],
        public readonly array $paymentMeans = [],
        public readonly array $allowanceCharges = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'invoiceNumber' => $this->invoiceNumber,
            'issueDate' => $this->issueDate,
            'dueDate' => $this->dueDate,
            'taxPointDate' => $this->taxPointDate,
            'currency' => $this->currency,
            'taxCurrencyCode' => $this->taxCurrencyCode,
            'buyerReference' => $this->buyerReference,
            'paymentTermsNote' => $this->paymentTermsNote,
            'orderReference' => $this->orderReference,
            'salesOrderReference' => $this->salesOrderReference,
            'projectReference' => $this->projectReference,
            'contractReference' => $this->contractReference,
            'receivingAdviceReference' => $this->receivingAdviceReference,
            'despatchAdviceReference' => $this->despatchAdviceReference,
            'tenderOrLotReference' => $this->tenderOrLotReference,
            'invoicedObjectId' => $this->invoicedObjectId,
            'invoicedObjectSchemeId' => $this->invoicedObjectSchemeId,
            'buyerAccountingReference' => $this->buyerAccountingReference,
            'notes' => $this->notes,
            'precedingInvoiceReferences' => array_map(
                static fn (PrecedingInvoiceReferenceInput $reference): array => $reference->toArray(),
                $this->precedingInvoiceReferences,
            ),
            'supportingDocuments' => array_map(
                static fn (SupportingDocumentInput $document): array => $document->toArray(),
                $this->supportingDocuments,
            ),
            'deliveryPeriodStart' => $this->deliveryPeriodStart,
            'deliveryPeriodEnd' => $this->deliveryPeriodEnd,
            'invoicePeriod' => $this->invoicePeriod?->toArray(),
            'delivery' => $this->delivery?->toArray(),
            'validationProfile' => $this->validationProfile->value,
            'supplier' => $this->supplier->toArray(),
            'buyer' => $this->buyer->toArray(),
            'payee' => $this->payee?->toArray(),
            'taxRepresentative' => $this->taxRepresentative?->toArray(),
            'lines' => array_map(static fn (LineInput $line): array => $line->toArray(), $this->lines),
            'paymentMeans' => array_map(
                static fn (PaymentMeansInput $means): array => $means->toArray(),
                $this->paymentMeans,
            ),
            'allowanceCharges' => array_map(
                static fn (AllowanceChargeInput $allowanceCharge): array => $allowanceCharge->toArray(),
                $this->allowanceCharges,
            ),
        ];
    }
}
