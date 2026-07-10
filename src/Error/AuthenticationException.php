<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** 401 -- no application-level error body exists for this status; the backend's
 * AuthenticationEntryPoint returns it independently of the standard error pipeline. */
final class AuthenticationException extends ApiException
{
    public function __construct(string $message, string $rawBody)
    {
        parent::__construct($message, 401, null, $rawBody);
    }
}
