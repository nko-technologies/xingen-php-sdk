<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class Address
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

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            streetName: Wire::str($w, 'streetName'),
            additionalStreetName: Wire::str($w, 'additionalStreetName'),
            addressLine3: Wire::str($w, 'addressLine3'),
            city: Wire::str($w, 'city'),
            postalZone: Wire::str($w, 'postalZone'),
            countrySubdivision: Wire::str($w, 'countrySubdivision'),
            countryCode: Wire::str($w, 'countryCode'),
        );
    }
}
