<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Postal address (BG-5/BG-8/BG-15) of a {@see PartyInput} or {@see DeliveryInput}. Only
 * `countryCode` is mandatory server-side, but the backend rejects a party with no address at
 * all -- pass at least `countryCode`. */
final class AddressInput
{
    public function __construct(
        public readonly ?string $streetName = null,
        public readonly ?string $additionalStreetName = null,
        public readonly ?string $addressLine3 = null,
        public readonly ?string $city = null,
        public readonly ?string $postalZone = null,
        public readonly ?string $countrySubdivision = null,
        public readonly ?string $countryCode = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'streetName' => $this->streetName,
            'additionalStreetName' => $this->additionalStreetName,
            'addressLine3' => $this->addressLine3,
            'city' => $this->city,
            'postalZone' => $this->postalZone,
            'countrySubdivision' => $this->countrySubdivision,
            'countryCode' => $this->countryCode,
        ];
    }
}
