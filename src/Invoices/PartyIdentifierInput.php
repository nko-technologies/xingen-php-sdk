<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Additional party identifier (BT-29/BT-46/BT-60), e.g. a SEPA creditor identifier. */
final class PartyIdentifierInput
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $schemeId = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'schemeId' => $this->schemeId,
        ];
    }
}
