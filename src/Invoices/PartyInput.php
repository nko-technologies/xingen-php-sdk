<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

final class PartyInput
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $vatId = null,
        public readonly ?string $leitwegId = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'vatId' => $this->vatId,
            'leitwegId' => $this->leitwegId,
        ];
    }
}
