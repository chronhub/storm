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
 */
final class StreamManager implements JsonSerializable
{
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @var Collection<string,int>
     */
    private Collection $streamPosition;

    /**
     * @param array<int<0,max>>     $retriesInMs      The array of retry durations in milliseconds.
     * @param non-empty-string|null $detectionWindows The detection window for resetting projection.
     */
    public function __construct(
        private readonly EventStreamLoader $eventStreamLoader,
        private readonly SystemClock $clock,
        public readonly array $retriesInMs,
        public readonly ?string $detectionWindows = null
    ) {
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
     * Merges remote/local stream positions.
     *
     * @param TStream $streamsPositions
     */
    public function syncStreams(array $streamsPositions): void
    {
        $this->streamPosition = $this->streamPosition->merge($streamsPositions);
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

        $this->resetGap();
    }

    public function all(): array
    {
        return $this->streamPosition->toArray();
    }

    /**
     * Return stream positions and confirmed gaps.
     *
     * @return array<string,int>
     */
    public function jsonSerialize(): array
    {
        return $this->all();
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
            return true;
        }

        // meant to speed up resetting projection
        if ($this->detectionWindows && ! $this->clock->isNowSubGreaterThan($this->detectionWindows, $eventTime)) {
            return true;
        }

        $this->gapDetected = true;

        return false;
    }

    private function resetGap(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }
}
