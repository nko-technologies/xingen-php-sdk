<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

use Xingen\Sdk\Internal\Wire;

final class ValidationError
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        public readonly ?string $field = null,
        public readonly ?string $suggestion = null,
        public readonly ?string $documentationUrl = null,
        public readonly ?ValidationLayer $layer = null,
        public readonly ?Severity $severity = null,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);
        $layer = Wire::str($w, 'layer');
        $severity = Wire::str($w, 'severity');

        return new self(
            code: Wire::str($w, 'code'),
            message: Wire::str($w, 'message'),
            field: Wire::str($w, 'field'),
            suggestion: Wire::str($w, 'suggestion'),
            documentationUrl: Wire::str($w, 'documentationUrl'),
            layer: $layer !== null ? ValidationLayer::tryFrom($layer) : null,
            severity: $severity !== null ? Severity::tryFrom($severity) : null,
        );
    }
}
