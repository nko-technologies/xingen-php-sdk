<?php

declare(strict_types=1);

namespace Xingen\Sdk\ApiKeys;

use Xingen\Sdk\Internal\Wire;

final class CreatedApiKey
{
    public function __construct(
        public readonly string $id,
        /** Shown only once -- the backend never returns it again. */
        public readonly string $rawKey,
        public readonly string $name,
        public readonly bool $sandbox = false,
        public readonly ?int $quotaLimit = null,
        public readonly string $createdAt = '',
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id') ?? '',
            rawKey: Wire::str($w, 'rawKey') ?? '',
            name: Wire::str($w, 'name') ?? '',
            sandbox: Wire::bool($w, 'sandbox'),
            quotaLimit: Wire::intOrNull($w, 'quotaLimit'),
            createdAt: Wire::str($w, 'createdAt') ?? '',
        );
    }
}
