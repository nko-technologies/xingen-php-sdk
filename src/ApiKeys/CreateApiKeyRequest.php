<?php

declare(strict_types=1);

namespace Xingen\Sdk\ApiKeys;

final class CreateApiKeyRequest
{
    public function __construct(
        public readonly string $name,
        /** Requests using this key don't count toward quota. */
        public readonly bool $sandbox = false,
        /** null = unlimited (Pro only; capped server-side regardless on the free tier). */
        public readonly ?int $quotaLimit = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sandbox' => $this->sandbox,
            'quotaLimit' => $this->quotaLimit,
        ];
    }
}
