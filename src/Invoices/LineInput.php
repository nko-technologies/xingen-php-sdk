<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

final class LineInput
{
    /** $quantity/$price/$taxRate/$grossPrice/$priceDiscount/$priceBaseQuantity are strings, not
     * floats -- PHP has no native arbitrary-precision decimal type, so exact literal precision
     * (e.g. "199.00", not 199.0) is only preserved by keeping these as strings end to end,
     * matching the TypeScript SDK's approach. The backend's Jackson-based deserializer accepts a
     * quoted numeric string for its BigDecimal fields without issue.
     *
     * @param list<ItemClassificationInput> $classifications
     * @param list<ItemAttributeInput> $attributes
     * @param list<LineAllowanceChargeInput> $allowanceCharges
     */
    public function __construct(
        public readonly string $description,
        public readonly string $quantity,
        public readonly ?string $unit,
        public readonly string $price,
        public readonly string $taxRate,
        /** Item name (BT-153), distinct from the free-text description if both are needed. */
        public readonly ?string $itemName = null,
        public readonly ?string $note = null,
        public readonly ?string $objectId = null,
        public readonly ?string $objectIdSchemeId = null,
        public readonly ?string $orderLineReference = null,
        public readonly ?string $accountingReference = null,
        public readonly ?string $sellerItemId = null,
        public readonly ?string $buyerItemId = null,
        public readonly ?string $standardItemId = null,
        public readonly ?string $standardItemIdSchemeId = null,
        public readonly ?string $originCountryCode = null,
        public readonly array $classifications = [],
        public readonly array $attributes = [],
        public readonly ?string $grossPrice = null,
        public readonly ?string $priceDiscount = null,
        public readonly ?string $priceBaseQuantity = null,
        public readonly ?string $priceBaseQuantityUnit = null,
        /** VAT category code, UNCL5305 (BT-151). Defaults to Standard rate if omitted. */
        public readonly ?string $taxCategoryCode = null,
        /** VAT exemption reason text (BT-120) -- set when taxCategoryCode is
         * exempt/reverse-charge/out-of-scope. */
        public readonly ?string $exemptionReason = null,
        /** VAT exemption reason code, UNCL5305 (BT-121). */
        public readonly ?string $exemptionReasonCode = null,
        public readonly ?InvoicePeriodInput $period = null,
        public readonly array $allowanceCharges = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'itemName' => $this->itemName,
            'note' => $this->note,
            'objectId' => $this->objectId,
            'objectIdSchemeId' => $this->objectIdSchemeId,
            'orderLineReference' => $this->orderLineReference,
            'accountingReference' => $this->accountingReference,
            'sellerItemId' => $this->sellerItemId,
            'buyerItemId' => $this->buyerItemId,
            'standardItemId' => $this->standardItemId,
            'standardItemIdSchemeId' => $this->standardItemIdSchemeId,
            'originCountryCode' => $this->originCountryCode,
            'classifications' => array_map(
                static fn (ItemClassificationInput $classification): array => $classification->toArray(),
                $this->classifications,
            ),
            'attributes' => array_map(
                static fn (ItemAttributeInput $attribute): array => $attribute->toArray(),
                $this->attributes,
            ),
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'grossPrice' => $this->grossPrice,
            'priceDiscount' => $this->priceDiscount,
            'priceBaseQuantity' => $this->priceBaseQuantity,
            'priceBaseQuantityUnit' => $this->priceBaseQuantityUnit,
            'taxCategoryCode' => $this->taxCategoryCode,
            'taxRate' => $this->taxRate,
            'exemptionReason' => $this->exemptionReason,
            'exemptionReasonCode' => $this->exemptionReasonCode,
            'period' => $this->period?->toArray(),
            'allowanceCharges' => array_map(
                static fn (LineAllowanceChargeInput $allowanceCharge): array => $allowanceCharge->toArray(),
                $this->allowanceCharges,
            ),
        ];
    }
}
