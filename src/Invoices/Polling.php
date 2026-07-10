<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

use Xingen\Sdk\Error\XingenCancellationException;
use Xingen\Sdk\Error\XingenTimeoutException;

final class Polling
{
    private function __construct()
    {
    }

    /**
     * Polls `$getFn` with exponential backoff until the invoice reaches a terminal status.
     *
     * Both VALIDATED and FAILED_VALIDATION count as terminal, successful SDK outcomes --
     * only cancellation, a timeout, or a transport failure throws. `$sleep`/`$now` are
     * injectable seams so tests can assert exact backoff durations and deterministic
     * timeout/cancellation behavior without any real wall-clock delay.
     *
     * @param callable(): InvoiceRecord $getFn
     * @param callable(float): void|null $sleep seconds
     * @param callable(): float|null $now monotonic seconds
     */
    public static function pollUntilTerminal(
        callable $getFn,
        string $invoiceId,
        PollOptions $options,
        ?callable $sleep = null,
        ?callable $now = null,
    ): InvoiceRecord {
        $sleep ??= static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };
        $now ??= static fn (): float => microtime(true);

        $deadline = $now() + $options->timeout;
        $interval = $options->initialInterval;
        $latest = $getFn();

        while (!$latest->status->isTerminal()) {
            if ($options->isCancelled()) {
                throw new XingenCancellationException("Polling for invoice {$invoiceId} was cancelled");
            }
            if ($now() > $deadline) {
                throw new XingenTimeoutException(
                    "Timed out waiting for invoice {$invoiceId} to reach a terminal status",
                    $latest,
                );
            }
            $sleep($interval);
            $interval = min($interval * $options->backoffMultiplier, $options->maxInterval);
            $latest = $getFn();
        }

        return $latest;
    }
}
