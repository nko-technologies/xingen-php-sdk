<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** 429 -- the backend returns a minimal `{"error": "..."}` shape here, written directly by a
 * security filter and bypassing the standard error pipeline, so no ErrorResponse is ever
 * attached. */
final class QuotaExceededException extends ApiException
{
    public function __construct(string $message, string $rawBody)
    {
        parent::__construct($message, 429, null, $rawBody);
    }
}
