<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Projector\GapRecognition;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function array_key_exists;
use function count;
use function usleep;

final class GapDetector implements GapRecognition
{
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @param array<int<0,max>> $retriesInMs The array of retry durations in milliseconds.
     */
    public function __construct(public readonly array $retriesInMs)
    {
        if ($this->retriesInMs === []) {
            throw new InvalidArgumentException('Provide at least one retry duration');
        }
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

    public function retryLeft(): int
    {
        return count($this->retriesInMs) - $this->retries;
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
