<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use DateInterval;
use DateTimeImmutable;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function count;
use function usleep;
use function array_key_exists;

class DetectGap
{
    protected int $retries = 0;

    protected bool $gapDetected = false;

    public function __construct(protected readonly StreamPosition $streamPosition,
                                protected readonly SystemClock $clock,
                                protected readonly array $retriesInMs,
                                protected readonly ?string $detectionWindows = null)
    {
    }

    public function detect(string $streamName, int $eventPosition, string $eventTime): bool
    {
        // no retries setup
        if (count($this->retriesInMs) === 0) {
            return false;
        }

        // the current event position match the next position
        if ($this->streamPosition->hasNextPosition($streamName, $eventPosition)) {
            $this->resetRetries();

            return $this->gapDetected = false;
        }

        $gapDetected = array_key_exists($this->retries, $this->retriesInMs);

        // when the max attempts of retries has been reached, we just give up
        // if most of the time, it just a fake gap causing by deadlock for ex
        // this can be a real issue cause the projection miss an event
        if (! $gapDetected) {
            $this->resetRetries();

            return $this->gapDetected = false;
        }

        // before marking a gap detected, we measure the time elapsed between
        // the event time and a detection windows
        // To use for resetting projection to avoid unnecessary retries
        if ($this->detectionWindows) {
            $pastDatetime = $this->clock->now()->sub(new DateInterval($this->detectionWindows));
            $eventDateTime = new DateTimeImmutable($eventTime, $pastDatetime->getTimezone());

            $gapDetected = $pastDatetime > $eventDateTime;

            if (! $gapDetected) {
                $this->resetRetries();
            }

            return $this->gapDetected = $gapDetected;
        }

        return $this->gapDetected = true;
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

    public function resetRetries(): void
    {
        $this->retries = 0;
    }
}
