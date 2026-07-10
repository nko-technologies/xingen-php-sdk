<?php

declare(strict_types=1);

namespace Xingen\Sdk\Internal;

final class FileUpload
{
    public function __construct(
        public readonly string $filename,
        public readonly string $content,
        public readonly string $contentType,
    ) {
    }
}
