<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Additional supporting document (BG-24). */
final class SupportingDocumentInput
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $schemeId = null,
        public readonly ?string $typeCode = null,
        public readonly ?string $description = null,
        public readonly ?string $externalUri = null,
        public readonly ?string $mimeCode = null,
        public readonly ?string $filename = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'schemeId' => $this->schemeId,
            'typeCode' => $this->typeCode,
            'description' => $this->description,
            'externalUri' => $this->externalUri,
            'mimeCode' => $this->mimeCode,
            'filename' => $this->filename,
        ];
    }
}
