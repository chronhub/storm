<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;

class Timer
{
    private ?DateTimeImmutable $now = null;

    public function __construct(
       private readonly SystemClock $clock,
       private readonly ?DateInterval $interval
    ) {
    }

    public function start(): void
    {
        if ($this->interval && ! $this->now instanceof DateTimeImmutable) {
            $this->now = $this->clock->now();
        }
    }

    public function isElapsed(): bool
    {
        if ($this->now === null) {
            return false;
        }

        return $this->clock->isNowSubGreaterThan($this->interval, $this->now);
    }
}
