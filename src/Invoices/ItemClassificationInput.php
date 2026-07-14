<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Item classification (BT-158). */
final class ItemClassificationInput
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $listId = null,
        public readonly ?string $listVersionId = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'listId' => $this->listId,
            'listVersionId' => $this->listVersionId,
        ];
    }
}
