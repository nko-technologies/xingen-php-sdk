<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

use Xingen\Sdk\Internal\Wire;

/** Standard error body shape. Present for 400/403/404/500 responses, not 401/429. */
final class ErrorResponse
{
    /** @param array<string, string>|null $fieldErrors */
    public function __construct(
        public readonly ?string $message,
        public readonly ?string $error,
        public readonly ?int $code,
        public readonly ?string $timestamp,
        public readonly ?array $fieldErrors,
    ) {
    }

    public static function fromWire(mixed $raw): self
    {
        $w = Wire::asWire($raw);

        return new self(
            message: Wire::str($w, 'message'),
            error: Wire::str($w, 'error'),
            code: Wire::intOrNull($w, 'code'),
            timestamp: Wire::str($w, 'timestamp'),
            fieldErrors: Wire::stringMap($w, 'fieldErrors'),
        );
    }
}
