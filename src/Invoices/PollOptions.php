<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

/** Configuration for the `*AndWait` polling helpers. Not part of the wire format, so unlike
 * the other invoice models this is a plain value object, not a fromWire()-hydrated one.
 *
 * Durations are plain float seconds, matching PHP's native `sleep`/`usleep` units and this
 * SDK's own microtime()-based clock, rather than inventing a duration type PHP doesn't have. */
final class PollOptions
{
    public function __construct(
        public readonly float $initialInterval = 0.5,
        public readonly float $maxInterval = 5.0,
        public readonly float $backoffMultiplier = 1.5,
        /** Total time budget for the whole poll loop, not a per-request timeout. */
        public readonly float $timeout = 60.0,
        /** Polled once per loop iteration; a true result aborts the wait with
         * XingenCancellationException. */
        public readonly ?\Closure $cancellationCheck = null,
    ) {
    }

    public function isCancelled(): bool
    {
        return $this->cancellationCheck !== null && ($this->cancellationCheck)();
    }
}
