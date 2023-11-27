<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use JsonSerializable;

use function array_key_exists;
use function usleep;

/**
 * todo make contract for persistent manager and non persistent
 *
 * @template TStream of array<non-empty-string,int>
 */
class StreamManager implements JsonSerializable
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
     * Binds a stream name to the next available position.
     *
     * @param int<1, max> $expectedPosition The incremented position of the current event.
     *
     * Successful bind in order:
     *      - event time is false ( meant for query projection )
     *      - no retry set
     *      - no gap detected
     *      - successful detection window checked
     *      - gap detected but no more retries available
     *
     * @throw RuntimeException When stream name is not watched
     */
    public function bind(string $streamName, int $expectedPosition, DateTimeImmutable|string|false $eventTime): bool
    {
        // checkMe: should we throw exception when expected position is less or equal than the current?

        if (! $this->streamPosition->has($streamName)) {
            throw new RuntimeException("Stream $streamName is not watched");
        }

        if (! $eventTime || $this->isGapFilled($streamName, $expectedPosition, $eventTime)) {
            $this->streamPosition[$streamName] = $expectedPosition;

            $this->resetGap();

            return true;
        }

        return false;
    }

    /**
     * Sleeps for the specified retry duration available.
     *
     * @throws RuntimeException When no gap is detected or no more retries are available.
     */
    public function sleep(): void
    {
        if (! $this->hasGap()) {
            throw new RuntimeException('No gap detected');
        }

        if (! $this->hasRetry()) {
            throw new RuntimeException('No more retries');
        }

        usleep($this->retriesInMs[$this->retries]);

        $this->retries++;
    }

    public function hasGap(): bool
    {
        return $this->gapDetected;
    }

    public function hasRetry(): bool
    {
        return array_key_exists($this->retries, $this->retriesInMs);
    }

    /**
     * Returns the current number of retries.
     *
     * @return int<0, max>
     */
    public function retries(): int
    {
        return $this->retries;
    }

    /**
     * Resets stream manager.
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
     * Return stream positions.
     *
     * @return array<string, int>
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    /**
     * Checks if there is no gap.
     */
    private function isGapFilled(string $streamName, int $expectedPosition, DateTimeImmutable|string $eventTime): bool
    {
        if ($this->retriesInMs === []) {
            return true;
        }

        if ($expectedPosition === $this->streamPosition[$streamName] + 1) {
            return true;
        }

        if (! $this->hasRetry()) {
            return true;
        }

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
