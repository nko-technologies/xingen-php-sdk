<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** Base class for HTTP 4xx/5xx responses. */
class ApiException extends XingenException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?ErrorResponse $errorResponse,
        public readonly string $rawBody,
    ) {
        parent::__construct($message);
    }
}
