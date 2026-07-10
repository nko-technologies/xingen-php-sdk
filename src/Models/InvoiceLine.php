<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class InvoiceLine
{
    /**
     * @param list<ItemClassification> $classifications
     * @param list<ItemAttribute> $attributes
     * @param list<LineAllowanceCharge> $allowanceCharges
     */
    public function __construct(
        public readonly ?string $lineId = null,
        public readonly ?string $note = null,
        public readonly ?string $objectId = null,
        public readonly ?string $objectIdSchemeId = null,
        public readonly ?string $orderLineReference = null,
        public readonly ?string $accountingReference = null,
        public readonly ?string $itemName = null,
        public readonly ?string $description = null,
        public readonly ?string $sellerItemId = null,
        public readonly ?string $buyerItemId = null,
        public readonly ?string $standardItemId = null,
        public readonly ?string $standardItemIdSchemeId = null,
        public readonly ?string $originCountryCode = null,
        public readonly array $classifications = [],
        public readonly array $attributes = [],
        public readonly ?string $quantity = null,
        public readonly ?string $unit = null,
        public readonly ?string $price = null,
        public readonly ?string $grossPrice = null,
        public readonly ?string $priceDiscount = null,
        public readonly bool $priceHasCharge = false,
        public readonly ?string $priceBaseQuantity = null,
        public readonly ?string $priceBaseQuantityUnit = null,
        public readonly ?string $taxCategoryCode = null,
        public readonly ?string $taxRate = null,
        public readonly ?string $lineNetAmount = null,
        /** null iff no line-level invoicing period was present in the source document. */
        public readonly ?InvoicePeriod $period = null,
        public readonly int $documentReferenceCount = 0,
        public readonly ?string $documentReferenceTypeCode = null,
        public readonly array $allowanceCharges = [],
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            lineId: Wire::str($w, 'lineId'),
            note: Wire::str($w, 'note'),
            objectId: Wire::str($w, 'objectId'),
            objectIdSchemeId: Wire::str($w, 'objectIdSchemeId'),
            orderLineReference: Wire::str($w, 'orderLineReference'),
            accountingReference: Wire::str($w, 'accountingReference'),
            itemName: Wire::str($w, 'itemName'),
            description: Wire::str($w, 'description'),
            sellerItemId: Wire::str($w, 'sellerItemId'),
            buyerItemId: Wire::str($w, 'buyerItemId'),
            standardItemId: Wire::str($w, 'standardItemId'),
            standardItemIdSchemeId: Wire::str($w, 'standardItemIdSchemeId'),
            originCountryCode: Wire::str($w, 'originCountryCode'),
            classifications: Wire::arr($w, 'classifications', ItemClassification::fromWire(...)),
            attributes: Wire::arr($w, 'attributes', ItemAttribute::fromWire(...)),
            quantity: Wire::str($w, 'quantity'),
            unit: Wire::str($w, 'unit'),
            price: Wire::str($w, 'price'),
            grossPrice: Wire::str($w, 'grossPrice'),
            priceDiscount: Wire::str($w, 'priceDiscount'),
            priceHasCharge: Wire::bool($w, 'priceHasCharge'),
            priceBaseQuantity: Wire::str($w, 'priceBaseQuantity'),
            priceBaseQuantityUnit: Wire::str($w, 'priceBaseQuantityUnit'),
            taxCategoryCode: Wire::str($w, 'taxCategoryCode'),
            taxRate: Wire::str($w, 'taxRate'),
            lineNetAmount: Wire::str($w, 'lineNetAmount'),
            period: Wire::obj($w, 'period', InvoicePeriod::fromWire(...)),
            documentReferenceCount: Wire::int($w, 'documentReferenceCount'),
            documentReferenceTypeCode: Wire::str($w, 'documentReferenceTypeCode'),
            allowanceCharges: Wire::arr($w, 'allowanceCharges', LineAllowanceCharge::fromWire(...)),
        );
    }
}
