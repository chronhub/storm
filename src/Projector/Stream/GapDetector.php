<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use function array_key_exists;
use function usleep;

class GapDetector
{
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @param array<int<0,max>>     $retriesInMs      The array of retry durations in milliseconds.
     * @param non-empty-string|null $detectionWindows The interval detection windows.
     */
    public function __construct(
        public readonly array $retriesInMs,
        public readonly ?string $detectionWindows = null
    ) {
        // todo probably remove detectionWindows as we can enforce replay from a checkpoint
        // checkMe enforce retriesInMs
    }

    public function isRecoverable(): bool
    {
        if (! $this->hasRetry()) {
            $this->reset();

            return false;
        }

        $this->gapDetected = true;

        return true;
    }

    public function hasGap(): bool
    {
        return $this->gapDetected;
    }

    public function hasRetry(): bool
    {
        return array_key_exists($this->retries, $this->retriesInMs);
    }

    public function sleep(): void
    {
        if (! $this->gapDetected) {
            return;
        }

        if (! $this->hasRetry()) {
            return;
        }

        usleep($this->retriesInMs[$this->retries]);

        $this->retries++;
    }

    public function reset(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }
}
