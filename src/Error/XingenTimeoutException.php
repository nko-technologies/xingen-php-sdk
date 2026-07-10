<?php

declare(strict_types=1);

namespace Xingen\Sdk\Error;

use Xingen\Sdk\Invoices\InvoiceRecord;

/** Raised when PollOptions::$timeout elapses before the invoice reaches a terminal status. */
final class XingenTimeoutException extends XingenException
{
    public function __construct(string $message, public readonly InvoiceRecord $partialResult)
    {
        parent::__construct($message);
    }
}
