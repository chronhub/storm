<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\StreamGapManager;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Reporter\DomainEvent;

use function array_key_exists;
use function usleep;

final class GapDetection implements StreamGapManager
{
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @param array<int<0,max>>     $retriesInMs      The array of retry durations in milliseconds.
     * @param non-empty-string|null $detectionWindows The interval detection windows.
     */
    public function __construct(
        private readonly StreamManager $streamManager,
        private readonly SystemClock $clock,
        public readonly array $retriesInMs,
        public readonly ?string $detectionWindows = null
    ) {
        // checkMe enforce retriesInMs
    }

    public function discover(array $queries): void
    {
        $this->streamManager->discover($queries);
    }

    public function sync(array $streamsPositions): void
    {
        $this->streamManager->sync($streamsPositions);
    }

    public function bind(string $streamName, int $expectedPosition, DomainEvent $event): bool
    {
        if ($this->isGapFilled($streamName, $expectedPosition, $event)) {
            $this->streamManager->bind($streamName, $expectedPosition, $event);

            $this->resetGap();

            return true;
        }

        return false;
    }

    public function hasStream(string $streamName): bool
    {
        return $this->streamManager->hasStream($streamName);
    }

    public function hasNextPosition(string $streamName, int $expectedPosition): bool
    {
        return $this->streamManager->hasNextPosition($streamName, $expectedPosition);
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
        $this->streamManager->resets();

        $this->resetGap();
    }

    public function all(): array
    {
        return $this->streamManager->all();
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }

    private function isGapFilled(string $streamName, int $expectedPosition, DomainEvent $event): bool
    {
        $noGap = match (true) {
            $this->hasNextPosition($streamName, $expectedPosition) => true,
            ! $this->hasRetry() => true,
            $this->isPastEventTime($event) => true,
            $this->retriesInMs === [] => true, // todo test already cover by hasRetry
            default => false,
        };

        if ($noGap) {
            return true;
        }

        $this->gapDetected = true;

        return false;
    }

    private function isPastEventTime(DomainEvent $event): bool
    {
        if ($this->detectionWindows === null) {
            return false;
        }

        return ! $this->clock->isNowSubGreaterThan($this->detectionWindows, $event->header(Header::EVENT_TIME));
    }

    private function resetGap(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }
}
