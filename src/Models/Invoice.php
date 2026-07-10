<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

/** Read-only invoice model, as returned inside an InvoiceRecord. */
final class Invoice
{
    /**
     * @param list<string> $notes
     * @param list<PrecedingInvoiceReference> $precedingInvoiceReferences
     * @param list<SupportingDocument> $supportingDocuments
     * @param list<InvoiceLine> $lines
     * @param list<TaxBreakdown> $taxBreakdowns
     * @param list<AllowanceCharge> $allowanceCharges
     * @param list<PaymentMeans> $paymentMeans
     */
    public function __construct(
        public readonly ?string $invoiceNumber = null,
        public readonly ?string $issueDate = null,
        public readonly ?string $dueDate = null,
        public readonly ?string $taxPointDate = null,
        public readonly ?string $currency = null,
        public readonly ?string $buyerReference = null,
        public readonly ?string $specificationId = null,
        public readonly ?string $profileId = null,
        public readonly ?string $typeCode = null,
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
        public readonly ?string $paymentTermsNote = null,
        public readonly array $precedingInvoiceReferences = [],
        public readonly array $supportingDocuments = [],
        public readonly int $projectReferenceCount = 0,
        public readonly ?string $deliveryPeriodStart = null,
        public readonly ?string $deliveryPeriodEnd = null,
        /** null iff no document-level invoicing period was present in the source document. */
        public readonly ?InvoicePeriod $invoicePeriod = null,
        /** null iff no delivery element was present in the source document. */
        public readonly ?Delivery $delivery = null,
        public readonly ?Party $supplier = null,
        public readonly ?Party $buyer = null,
        /** null unless the payee differs from the seller. */
        public readonly ?Party $payee = null,
        /** null unless a tax representative is present. */
        public readonly ?Party $taxRepresentative = null,
        public readonly array $lines = [],
        public readonly array $taxBreakdowns = [],
        public readonly array $allowanceCharges = [],
        public readonly array $paymentMeans = [],
        public readonly ?string $taxCurrencyCode = null,
        public readonly int $taxTotalWithSubtotalsCount = 0,
        public readonly int $taxTotalWithoutSubtotalsCount = 0,
        public readonly ?string $lineExtensionAmount = null,
        public readonly ?string $allowanceTotalAmount = null,
        public readonly ?string $chargeTotalAmount = null,
        public readonly ?string $taxExclusiveAmount = null,
        public readonly ?string $taxAmount = null,
        public readonly ?string $taxAmountInAccountingCurrency = null,
        public readonly ?string $taxInclusiveAmount = null,
        public readonly ?string $prepaidAmount = null,
        public readonly ?string $payableRoundingAmount = null,
        public readonly ?string $payableAmount = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            invoiceNumber: Wire::str($w, 'invoiceNumber'),
            issueDate: Wire::str($w, 'issueDate'),
            dueDate: Wire::str($w, 'dueDate'),
            taxPointDate: Wire::str($w, 'taxPointDate'),
            currency: Wire::str($w, 'currency'),
            buyerReference: Wire::str($w, 'buyerReference'),
            specificationId: Wire::str($w, 'specificationId'),
            profileId: Wire::str($w, 'profileId'),
            typeCode: Wire::str($w, 'typeCode'),
            orderReference: Wire::str($w, 'orderReference'),
            salesOrderReference: Wire::str($w, 'salesOrderReference'),
            projectReference: Wire::str($w, 'projectReference'),
            contractReference: Wire::str($w, 'contractReference'),
            receivingAdviceReference: Wire::str($w, 'receivingAdviceReference'),
            despatchAdviceReference: Wire::str($w, 'despatchAdviceReference'),
            tenderOrLotReference: Wire::str($w, 'tenderOrLotReference'),
            invoicedObjectId: Wire::str($w, 'invoicedObjectId'),
            invoicedObjectSchemeId: Wire::str($w, 'invoicedObjectSchemeId'),
            buyerAccountingReference: Wire::str($w, 'buyerAccountingReference'),
            notes: Wire::strArr($w, 'notes'),
            paymentTermsNote: Wire::str($w, 'paymentTermsNote'),
            precedingInvoiceReferences: Wire::arr(
                $w,
                'precedingInvoiceReferences',
                PrecedingInvoiceReference::fromWire(...),
            ),
            supportingDocuments: Wire::arr($w, 'supportingDocuments', SupportingDocument::fromWire(...)),
            projectReferenceCount: Wire::int($w, 'projectReferenceCount'),
            deliveryPeriodStart: Wire::str($w, 'deliveryPeriodStart'),
            deliveryPeriodEnd: Wire::str($w, 'deliveryPeriodEnd'),
            invoicePeriod: Wire::obj($w, 'invoicePeriod', InvoicePeriod::fromWire(...)),
            delivery: Wire::obj($w, 'delivery', Delivery::fromWire(...)),
            supplier: Wire::obj($w, 'supplier', Party::fromWire(...)),
            buyer: Wire::obj($w, 'buyer', Party::fromWire(...)),
            payee: Wire::obj($w, 'payee', Party::fromWire(...)),
            taxRepresentative: Wire::obj($w, 'taxRepresentative', Party::fromWire(...)),
            lines: Wire::arr($w, 'lines', InvoiceLine::fromWire(...)),
            taxBreakdowns: Wire::arr($w, 'taxBreakdowns', TaxBreakdown::fromWire(...)),
            allowanceCharges: Wire::arr($w, 'allowanceCharges', AllowanceCharge::fromWire(...)),
            paymentMeans: Wire::arr($w, 'paymentMeans', PaymentMeans::fromWire(...)),
            taxCurrencyCode: Wire::str($w, 'taxCurrencyCode'),
            taxTotalWithSubtotalsCount: Wire::int($w, 'taxTotalWithSubtotalsCount'),
            taxTotalWithoutSubtotalsCount: Wire::int($w, 'taxTotalWithoutSubtotalsCount'),
            lineExtensionAmount: Wire::str($w, 'lineExtensionAmount'),
            allowanceTotalAmount: Wire::str($w, 'allowanceTotalAmount'),
            chargeTotalAmount: Wire::str($w, 'chargeTotalAmount'),
            taxExclusiveAmount: Wire::str($w, 'taxExclusiveAmount'),
            taxAmount: Wire::str($w, 'taxAmount'),
            taxAmountInAccountingCurrency: Wire::str($w, 'taxAmountInAccountingCurrency'),
            taxInclusiveAmount: Wire::str($w, 'taxInclusiveAmount'),
            prepaidAmount: Wire::str($w, 'prepaidAmount'),
            payableRoundingAmount: Wire::str($w, 'payableRoundingAmount'),
            payableAmount: Wire::str($w, 'payableAmount'),
        );
    }
}
