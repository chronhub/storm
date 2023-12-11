<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;

/**
 * @deprecated
 */
class Timer
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

    public function isElapsed(): bool
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
}
