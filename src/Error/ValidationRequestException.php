<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** 400 -- malformed request body or bean-validation failure. */
final class ValidationRequestException extends ApiException
{
    /** @var array<string, string> */
    public readonly array $fieldErrors;

    public function __construct(string $message, ?ErrorResponse $errorResponse, string $rawBody)
    {
        parent::__construct($message, 400, $errorResponse, $rawBody);
        $this->fieldErrors = $errorResponse?->fieldErrors ?? [];
    }
}
