<?php

declare(strict_types=1);

namespace Xingen\Sdk\ApiKeys;

use Xingen\Sdk\Internal\Wire;

final class ApiKey
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $keyPrefix,
        public readonly bool $sandbox = false,
        public readonly bool $active = true,
        /** null = unlimited. */
        public readonly ?int $quotaLimit = null,
        public readonly int $quotaUsed = 0,
        public readonly ?string $lastUsedAt = null,
        public readonly string $createdAt = '',
        /** null if the key is still active. */
        public readonly ?string $revokedAt = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            id: Wire::str($w, 'id') ?? '',
            name: Wire::str($w, 'name') ?? '',
            keyPrefix: Wire::str($w, 'keyPrefix') ?? '',
            sandbox: Wire::bool($w, 'sandbox'),
            active: Wire::bool($w, 'active', true),
            quotaLimit: Wire::intOrNull($w, 'quotaLimit'),
            quotaUsed: Wire::int($w, 'quotaUsed'),
            lastUsedAt: Wire::str($w, 'lastUsedAt'),
            createdAt: Wire::str($w, 'createdAt') ?? '',
            revokedAt: Wire::str($w, 'revokedAt'),
        );
    }
}
