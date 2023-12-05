<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use DateTimeImmutable;
use Illuminate\Support\Collection;

use function array_key_exists;
use function usleep;

final class StreamManager implements StreamManagerInterface
{
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @var Collection<string,int>
     */
    private Collection $streamPosition;

    /**
     * @param array<int<0,max>>     $retriesInMs      The array of retry durations in milliseconds.
     * @param non-empty-string|null $detectionWindows The detection windows.
     */
    public function __construct(
        private readonly EventStreamLoader $eventStreamLoader,
        private readonly SystemClock $clock,
        public readonly array $retriesInMs,
        public readonly ?string $detectionWindows = null
    ) {
        $this->streamPosition = new Collection();
    }

    public function discover(array $queries): void
    {
        $container = $this->eventStreamLoader
            ->loadFrom($queries)
            ->mapWithKeys(fn (string $streamName): array => [$streamName => 0]);

        $this->streamPosition = $container->merge($this->streamPosition);
    }

    public function merge(array $streamsPositions): void
    {
        $this->streamPosition = $this->streamPosition->merge($streamsPositions);
    }

    public function bind(string $streamName, int $expectedPosition, DateTimeImmutable|string|false $eventTime): bool
    {
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

    public function retries(): int
    {
        return $this->retries;
    }

    public function resets(): void
    {
        $this->streamPosition = new Collection();

        $this->resetGap();
    }

    public function all(): array
    {
        return $this->streamPosition->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }

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
