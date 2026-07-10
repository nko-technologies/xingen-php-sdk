<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

use Throwable;

/** A network/transport-level failure (connection refused, DNS failure, timeout, etc.) -- not
 * an HTTP error response. */
final class XingenIOException extends XingenException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
