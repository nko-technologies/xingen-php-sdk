<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

final class LineInput
{
    /** $quantity/$price/$taxRate are strings, not floats -- PHP has no native
     * arbitrary-precision decimal type, so exact literal precision (e.g. "199.00", not
     * 199.0) is only preserved by keeping these as strings end to end, matching the
     * TypeScript SDK's approach. The backend's Jackson-based deserializer accepts a
     * quoted numeric string for its BigDecimal fields without issue. */
    public function __construct(
        public readonly string $description,
        public readonly string $quantity,
        public readonly ?string $unit,
        public readonly string $price,
        public readonly string $taxRate,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'taxRate' => $this->taxRate,
        ];
    }
}
