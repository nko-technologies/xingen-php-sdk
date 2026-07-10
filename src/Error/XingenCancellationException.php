<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

/** Raised by the *AndWait polling helpers when the caller's cancellationCheck returns true,
 * or when sleeping between polls is interrupted. */
final class XingenCancellationException extends XingenException
{
}
