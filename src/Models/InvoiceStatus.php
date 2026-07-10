<?php

declare(strict_types=1);

namespace Xingen\Sdk\Models;

/**
 * Lifecycle status of a submitted invoice.
 *
 * Wire values are lowercase snake_case, unlike the other enums in this SDK, which matches
 * the literal string values the backend persists (not a generic camelCase or enum-name
 * serialization).
 */
enum InvoiceStatus: string
{
    case PROCESSING = 'processing';
    case VALIDATED = 'validated';
    case FAILED_VALIDATION = 'failed_validation';

    public function isTerminal(): bool
    {
        return $this !== self::PROCESSING;
    }
}
