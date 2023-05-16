<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateInterval;
use DateTimeImmutable;

class Timer
{
    private ?DateTimeImmutable $now;

    public function __construct(
       private readonly SystemClock $clock,
       private readonly ?DateInterval $interval
    ) {
    }

    public function start(): void
    {
        $this->now = $this->interval ? $this->clock->now() : null;
    }

    public function isNotElapsed(): bool
    {
        if ($this->now === null) {
            return true;
        }

        return ! $this->clock->isNowSubGreaterThan($this->interval, $this->now);
    }
}
