<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Contact details (BG-6/BG-9) of a {@see PartyInput}. */
final class ContactInput
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $telephone = null,
        public readonly ?string $email = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'telephone' => $this->telephone,
            'email' => $this->email,
        ];
    }
}
