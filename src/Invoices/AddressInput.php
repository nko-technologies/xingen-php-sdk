<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Postal address (BG-5/BG-8) of a {@see PartyInput}. Only `countryCode` is mandatory
 * server-side, but the backend rejects a party with no address at all -- pass at least
 * `countryCode`. */
final class AddressInput
{
    public function __construct(
        public readonly ?string $streetName = null,
        public readonly ?string $city = null,
        public readonly ?string $postalZone = null,
        public readonly ?string $countryCode = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'streetName' => $this->streetName,
            'city' => $this->city,
            'postalZone' => $this->postalZone,
            'countryCode' => $this->countryCode,
        ];
    }
}
