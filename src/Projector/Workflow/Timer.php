<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;

class Timer
{
    private ?DateTimeImmutable $startTime = null;

    private ?DateInterval $interval = null;

    public function __construct(protected readonly SystemClock $clock)
    {
    }

    public function start(): void
    {
        if (! $this->startTime instanceof DateTimeImmutable) {
            $this->startTime = $this->clock->now();
        }
    }

    public function isExpired(): bool
    {
        if ($this->interval === null) {
            return false;
        }

        return $this->clock->isNowSubGreaterThan($this->interval, $this->startTime);
    }

    public function isStarted(): bool
    {
        return $this->startTime instanceof DateTimeImmutable;
    }

    public function getTimestamp(): int
    {
        return $this->startTime->getTimestamp();
    }

    public function getElapsedTime(): DateInterval
    {
        return $this->clock->now()->diff($this->startTime);
    }

    public function reset(): void
    {
        $this->startTime = null;
    }

    public function setInterval(?DateInterval $interval): void
    {
        $this->interval = $interval;
    }
}
