<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use JsonSerializable;
use LogicException;

use function array_key_exists;
use function usleep;

final class StreamManager implements JsonSerializable
{
    protected int $retries = 0;

    protected bool $gapDetected = false;

    protected GapsCollection $gaps;

    /**
     * @var Collection<string,int>
     */
    private Collection $streamPosition;

    public function __construct(
        private readonly EventStreamLoader $eventStreamLoader,
        private readonly SystemClock $clock,
        private readonly array $retriesInMs,
        private readonly ?string $detectionWindows = null
    ) {
        $this->gaps = new GapsCollection();
        $this->streamPosition = new Collection();
    }

    public function detectGap(string $streamName, int $eventPosition, DateTimeImmutable|string $eventTime): bool
    {
        if ($this->retriesInMs === []) {
            return false;
        }

        $gapDetected = $this->isGapDetected($streamName, $eventPosition);

        if (! $gapDetected) {
            $this->resetGap();

            return false;
        }

        // meant to speed up resetting projection
        if ($this->detectionWindows && ! $this->clock->isNowSubGreaterThan($this->detectionWindows, $eventTime)) {
            $this->resetGap();

            return false;
        }

        return $this->gapDetected = true;
    }

    /**
     * @param array $streamGaps<int>
     */
    public function mergeGaps(array $streamGaps): void
    {
        $this->gaps->merge($streamGaps);
    }

    public function confirmedGaps(): array
    {
        return $this->gaps->filterConfirmedGaps();
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

    public function watchStreams(array $queries): void
    {
        $container = $this->eventStreamLoader
            ->loadFrom($queries)
            ->mapWithKeys(fn (string $streamName): array => [$streamName => 0]);

        $this->streamPosition = $container->merge($this->streamPosition);
    }

    /**
     * @param array<string,int> $streamsPositions
     */
    public function discoverStreams(array $streamsPositions): void
    {
        $this->streamPosition = $this->streamPosition->merge($streamsPositions);
    }

    public function bind(string $streamName, int $position): void
    {
        $this->streamPosition[$streamName] = $position;
    }

    public function resets(): void
    {
        $this->streamPosition = new Collection();

        $this->gaps = new GapsCollection();
    }

    public function jsonSerialize(): array
    {
        return $this->streamPosition->toArray();
    }

    private function isGapDetected(string $streamName, int $eventPosition): bool
    {
        $hasNextPosition = $eventPosition === $this->streamPosition[$streamName] + 1;

        if ($hasNextPosition) {
            $this->gaps->remove($eventPosition);

            $this->resetGap();

            return false;
        }

        $this->gaps->put($eventPosition, ! $this->hasRetry());

        return ! $this->hasRetry();
    }

    private function resetGap(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }
}
