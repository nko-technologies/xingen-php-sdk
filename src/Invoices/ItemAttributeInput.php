<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Additional item attribute (BG-32). */
final class ItemAttributeInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }
}
