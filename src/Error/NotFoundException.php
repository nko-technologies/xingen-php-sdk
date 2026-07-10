<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** 404. */
final class NotFoundException extends ApiException
{
    public function __construct(string $message, ?ErrorResponse $errorResponse, string $rawBody)
    {
        parent::__construct($message, 404, $errorResponse, $rawBody);
    }
}
