<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class Delivery
{
    public function __construct(
        public readonly ?string $partyName = null,
        public readonly ?string $locationId = null,
        public readonly ?string $locationSchemeId = null,
        /** Deliver-to address (BG-15); null iff absent from the source document. */
        public readonly ?Address $address = null,
        public readonly ?string $actualDeliveryDate = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            partyName: Wire::str($w, 'partyName'),
            locationId: Wire::str($w, 'locationId'),
            locationSchemeId: Wire::str($w, 'locationSchemeId'),
            address: Wire::obj($w, 'address', Address::fromWire(...)),
            actualDeliveryDate: Wire::str($w, 'actualDeliveryDate'),
        );
    }
}
