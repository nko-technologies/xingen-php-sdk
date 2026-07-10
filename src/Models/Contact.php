<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class Contact
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $telephone = null,
        public readonly ?string $email = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            name: Wire::str($w, 'name'),
            telephone: Wire::str($w, 'telephone'),
            email: Wire::str($w, 'email'),
        );
    }
}
