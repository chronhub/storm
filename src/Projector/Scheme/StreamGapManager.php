<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateTimeImmutable;
use LogicException;

use function array_key_exists;
use function usleep;

class StreamGapManager
{
    protected int $retries = 0;

    protected bool $gapDetected = false;

    protected GapsCollection $gaps;

    public function __construct(
        protected readonly StreamPosition $streamPosition,
        protected readonly SystemClock $clock,
        protected readonly array $retriesInMs,
        protected readonly ?string $detectionWindows = null
    ) {
        $this->gaps = new GapsCollection();
    }

    public function detect(string $streamName, int $eventPosition, string|DateTimeImmutable $eventTime): bool
    {
        if ($this->retriesInMs === []) {
            return false;
        }

        $gapDetected = $this->isGapDetected($streamName, $eventPosition);

        if (! $gapDetected) {
            $this->reset();

            return false;
        }

        // meant to sped up resetting projection
        if ($this->detectionWindows && ! $this->clock->isNowSubGreaterThan($this->detectionWindows, $eventTime)) {
            $this->reset();

            return false;
        }

        return $this->gapDetected = true;
    }

    public function mergeGaps(string $streamName, array $streamGaps): void
    {
        $this->gaps->merge($streamName, $streamGaps);
    }

    public function resetGaps(): void
    {
        $this->gaps = new GapsCollection();
    }

    public function hasGap(): bool
    {
        return $this->gapDetected;
    }

    public function sleep(): void
    {
        if ($this->retriesInMs === []) {
            return;
        }

        if (! $this->hasRetry()) {
            throw new LogicException('No more retries');
        }

        usleep($this->retriesInMs[$this->retries]);

        $this->retries++;
    }

    public function hasRetry(): bool
    {
        return array_key_exists($this->retries, $this->retriesInMs);
    }

    public function retries(): int
    {
        return $this->retries;
    }

    /**
     * Retrieves an array of confirmed event positions for the specified stream.
     *
     * @param  non-empty-string  $streamName The name of the stream.
     * @return array<int<1,max>> An array of confirmed event positions.
     */
    public function getConfirmedGaps(string $streamName): array
    {
        return $this->gaps->filterConfirmedGaps($streamName);
    }

    protected function isGapDetected(string $streamName, int $eventPosition): bool
    {
        if ($this->streamPosition->hasNextPosition($streamName, $eventPosition)) {
            $this->gaps->remove($streamName, $eventPosition);

            $this->reset();

            return false;
        }

        $this->gaps->put($streamName, $eventPosition, ! $this->hasRetry());

        return ! $this->hasRetry();
    }

    protected function reset(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }
}
