<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class Party
{
    /** @param list<PartyIdentifier> $identifiers */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $registrationName = null,
        public readonly ?string $vatId = null,
        public readonly ?string $taxRegistrationId = null,
        public readonly ?string $legalRegistrationId = null,
        public readonly ?string $legalRegistrationSchemeId = null,
        public readonly ?string $additionalLegalInfo = null,
        public readonly ?string $leitwegId = null,
        public readonly ?string $endpointId = null,
        public readonly ?string $endpointSchemeId = null,
        public readonly array $identifiers = [],
        /** null iff no postal address element was present in the source document. */
        public readonly ?Address $address = null,
        /** null iff no contact element was present in the source document. */
        public readonly ?Contact $contact = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            name: Wire::str($w, 'name'),
            registrationName: Wire::str($w, 'registrationName'),
            vatId: Wire::str($w, 'vatId'),
            taxRegistrationId: Wire::str($w, 'taxRegistrationId'),
            legalRegistrationId: Wire::str($w, 'legalRegistrationId'),
            legalRegistrationSchemeId: Wire::str($w, 'legalRegistrationSchemeId'),
            additionalLegalInfo: Wire::str($w, 'additionalLegalInfo'),
            leitwegId: Wire::str($w, 'leitwegId'),
            endpointId: Wire::str($w, 'endpointId'),
            endpointSchemeId: Wire::str($w, 'endpointSchemeId'),
            identifiers: Wire::arr($w, 'identifiers', PartyIdentifier::fromWire(...)),
            address: Wire::obj($w, 'address', Address::fromWire(...)),
            contact: Wire::obj($w, 'contact', Contact::fromWire(...)),
        );
    }
}
