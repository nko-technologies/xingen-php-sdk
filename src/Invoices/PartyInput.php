<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Party (buyer, seller, payee, or tax representative), as submitted with a new invoice. */
final class PartyInput
{
    /** @param list<PartyIdentifierInput> $identifiers */
    public function __construct(
        public readonly string $name,
        /** Legal registration name, if different from the trading name (BT-27/BT-44). */
        public readonly ?string $registrationName = null,
        public readonly ?string $vatId = null,
        /** Tax registration identifier, non-VAT scheme (BT-32). */
        public readonly ?string $taxRegistrationId = null,
        /** Legal registration identifier (BT-30/BT-47). */
        public readonly ?string $legalRegistrationId = null,
        /** Legal registration identifier scheme (BT-30-1/BT-47-1). */
        public readonly ?string $legalRegistrationSchemeId = null,
        /** Additional legal information, e.g. legal form (BT-33). */
        public readonly ?string $additionalLegalInfo = null,
        public readonly ?string $leitwegId = null,
        /** Postal address (BG-5/BG-8) -- mandatory for every profile for supplier/buyer; the
         * backend rejects a party with no address. */
        public readonly ?AddressInput $address = null,
        public readonly ?ContactInput $contact = null,
        public readonly ?string $endpointId = null,
        public readonly ?string $endpointSchemeId = null,
        /** Additional party identifiers (BT-29/BT-46/BT-60), e.g. a SEPA creditor identifier. */
        public readonly array $identifiers = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'registrationName' => $this->registrationName,
            'vatId' => $this->vatId,
            'taxRegistrationId' => $this->taxRegistrationId,
            'legalRegistrationId' => $this->legalRegistrationId,
            'legalRegistrationSchemeId' => $this->legalRegistrationSchemeId,
            'additionalLegalInfo' => $this->additionalLegalInfo,
            'leitwegId' => $this->leitwegId,
            'address' => $this->address?->toArray(),
            'contact' => $this->contact?->toArray(),
            'endpointId' => $this->endpointId,
            'endpointSchemeId' => $this->endpointSchemeId,
            'identifiers' => array_map(
                static fn (PartyIdentifierInput $identifier): array => $identifier->toArray(),
                $this->identifiers,
            ),
        ];
    }
}
