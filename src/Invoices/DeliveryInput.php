<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Delivery information (BG-13). */
final class DeliveryInput
{
    public function __construct(
        public readonly ?string $partyName = null,
        public readonly ?string $locationId = null,
        public readonly ?string $locationSchemeId = null,
        public readonly ?AddressInput $address = null,
        public readonly ?string $actualDeliveryDate = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'partyName' => $this->partyName,
            'locationId' => $this->locationId,
            'locationSchemeId' => $this->locationSchemeId,
            'address' => $this->address?->toArray(),
            'actualDeliveryDate' => $this->actualDeliveryDate,
        ];
    }
}
