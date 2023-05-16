<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
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
        if ($this->now !== null) {
            throw new RuntimeException('Timer already started');
        }

        $this->now = $this->interval ? $this->clock->now() : null;
    }

    public function isElapsed(): bool
    {
        if ($this->now === null) {
            return false;
        }

        return $this->clock->isNowSubGreaterThan($this->interval, $this->now);
    }
}
