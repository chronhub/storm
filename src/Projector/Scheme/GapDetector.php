<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use DateTimeImmutable;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function usleep;
use function array_key_exists;

class GapDetector
{
    protected int $retries = 0;

    protected bool $gapDetected = false;

    public function __construct(
        protected readonly StreamPosition $streamPosition,
        protected readonly SystemClock $clock,
        protected readonly array $retriesInMs,
        protected readonly ?string $detectionWindows = null
    ) {
    }

    public function detect(string $streamName, int $eventPosition, string|DateTimeImmutable $eventTime): bool
    {
        if (empty($this->retriesInMs)) {
            return false;
        }

        $gapDetected = $this->isGapDetected($streamName, $eventPosition);

        if (! $gapDetected) {
            $this->resetRetries();

            $this->resetGap();

            return false;
        }

        if ($this->detectionWindows && ! $this->isElapsed($eventTime)) {
            $this->resetRetries();

            $this->resetGap();

            return false;
        }

        //fixMe log/dispatch event ?
        $this->gapDetected = true;

        return true;
    }

    public function hasGap(): bool
    {
        return $this->gapDetected;
    }

    public function sleep(): void
    {
        if (! array_key_exists($this->retries, $this->retriesInMs)) {
            return;
        }

        usleep($this->retriesInMs[$this->retries]);

        $this->retries++;
    }

    public function resetGap(): void
    {
        $this->gapDetected = false;
    }

    public function retries(): int
    {
        return $this->retries;
    }

    protected function isGapDetected(string $streamName, int $eventPosition): bool
    {
        if ($this->streamPosition->hasNextPosition($streamName, $eventPosition)) {
            $this->resetRetries();

            return false;
        }

        return array_key_exists($this->retries, $this->retriesInMs);
    }

    protected function isElapsed(string|DateTimeImmutable $eventTime): bool
    {
        return $this->clock->isNowSubGreaterThan($this->detectionWindows, $eventTime);
    }

    protected function resetRetries(): void
    {
        $this->retries = 0;
    }
}
