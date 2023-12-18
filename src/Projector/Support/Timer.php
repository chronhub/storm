<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;

final class Timer
{
    private ?DateTimeImmutable $startTime = null;

    public function __construct(
        private readonly SystemClock $clock,
        private readonly ?DateInterval $interval
    ) {
    }

    public function start(): void
    {
        if ($this->interval && ! $this->startTime instanceof DateTimeImmutable) {
            $this->startTime = $this->clock->now();
        }
    }

    public function isExpired(): bool
    {
        if ($this->startTime === null) {
            return false;
        }

        return $this->clock->isNowSubGreaterThan($this->interval, $this->startTime);
    }

    public function isStarted(): bool
    {
        if ($this->interval === null) {
            return true;
        }

        return $this->startTime instanceof DateTimeImmutable;
    }

    public function getElapsedTime(): ?int
    {
        if ($this->startTime === null) {
            return null;
        }

        $now = $this->clock->now();

        return $now->getTimestamp() - $this->startTime->getTimestamp();
    }
}
