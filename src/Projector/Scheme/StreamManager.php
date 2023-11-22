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

/**
 * todo make contract for persistent manager and non persistent
 *
 * @template TStream of array<non-empty-string,int>
 * @template TGap of array<int<1,max>>
 */
final class StreamManager implements JsonSerializable
{
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @var GapsCollection{array<int,bool} GapsCollection
     */
    private GapsCollection $gaps;

    /**
     * @var Collection<string,int>
     */
    private Collection $streamPosition;

    /**
     * @param array       $retriesInMs      The array of retry durations in milliseconds.
     * @param string|null $detectionWindows The detection window for resetting projection.
     */
    public function __construct(
        private readonly EventStreamLoader $eventStreamLoader,
        private readonly SystemClock $clock,
        private readonly array $retriesInMs,
        private readonly ?string $detectionWindows = null
    ) {
        $this->gaps = new GapsCollection();
        $this->streamPosition = new Collection();
    }

    /**
     * Watches event streams based on given queries.
     *
     * @param array{all?: bool, categories?: string[], names?: string[]} $queries
     */
    public function watchStreams(array $queries): void
    {
        $container = $this->eventStreamLoader
            ->loadFrom($queries)
            ->mapWithKeys(fn (string $streamName): array => [$streamName => 0]);

        $this->streamPosition = $container->merge($this->streamPosition);
    }

    /**
     * Merges remote/local stream positions and gaps.
     *
     * @param TStream           $streamsPositions
     * @param array<int<1,max>> $streamGaps
     */
    public function syncStreams(array $streamsPositions, array $streamGaps): void
    {
        $this->streamPosition = $this->streamPosition->merge($streamsPositions);

        $this->gaps->merge($streamGaps);
    }

    /**
     * Binds a stream name to a position, handling gaps and retries.
     *
     * note that false event time is used for non-persistent subscription
     * as they do not use gap detection
     */
    public function bind(string $streamName, int $position, DateTimeImmutable|string|false $eventTime): bool
    {
        if (! $this->streamPosition->has($streamName)) {
            throw new LogicException("Stream $streamName not watched");
        }

        if (! $eventTime || $this->isGapFilled($streamName, $position, $eventTime) || ! $this->hasRetry()) {
            $this->streamPosition[$streamName] = $position;

            $this->resetGap();

            return true;
        }

        return false;
    }

    public function hasGap(): bool
    {
        return $this->gapDetected;
    }

    /**
     * Sleeps for the specified retry duration.
     *
     * @throws LogicException When no gap is detected or no more retries are available.
     */
    public function sleep(): void
    {
        if ($this->retriesInMs === []) {
            return;
        }

        if (! $this->hasGap()) {
            throw new LogicException('No gap detected');
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
     * Resets steam manager.
     */
    public function resets(): void
    {
        $this->streamPosition = new Collection();

        $this->gaps = new GapsCollection();

        $this->resetGap();
    }

    public function getStreamPositions(): array
    {
        return $this->streamPosition->toArray();
    }

    /**
     * Return all gaps confirmed or not.
     *
     * @return array<int,bool>
     */
    public function getGaps(): array
    {
        return $this->gaps->all()->toArray();
    }

    /**
     * Return stream positions and confirmed gaps.
     *
     * note: this method should only be called to persist projection
     * as we filter unconfirmed gaps locally
     *
     * @return array{positions: array<string,int>, gaps: array<int>}
     */
    public function jsonSerialize(): array
    {
        return [
            'positions' => $this->getStreamPositions(),
            'gaps' => $this->gaps->filterConfirmedGaps(),
        ];
    }

    /**
     * Checks if there is no gap and updates the gaps collection.
     */
    private function isGapFilled(string $streamName, int $eventPosition, DateTimeImmutable|string $eventTime): bool
    {
        if ($this->retriesInMs === []) {
            return true;
        }

        if ($eventPosition === $this->streamPosition[$streamName] + 1) {
            $this->gaps->remove($eventPosition);

            return true;
        }

        // meant to speed up resetting projection
        if ($this->detectionWindows && ! $this->clock->isNowSubGreaterThan($this->detectionWindows, $eventTime)) {
            return true;
        }

        $this->gaps->put($eventPosition, ! $this->hasRetry());

        $this->gapDetected = true;

        return false;
    }

    private function resetGap(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }
}
