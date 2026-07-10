<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** 403 -- e.g. the invoice/API key exists but is not owned by the caller. */
final class PermissionException extends ApiException
{
    public function __construct(string $message, ?ErrorResponse $errorResponse, string $rawBody)
    {
        parent::__construct($message, 403, $errorResponse, $rawBody);
    }
}
