<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Projector\Workflow\Timer;
use DateInterval;

class TimeWatcher
{
    public function __construct(protected readonly Timer $timer)
    {
    }

    public function start(): void
    {
        $this->timer->start();
    }

    public function isExpired(): bool
    {
        return $this->timer->isExpired();
    }

    public function isStarted(): bool
    {
        return $this->timer->isStarted();
    }

    public function reset(): void
    {
        $this->timer->reset();
    }

    public function getCurrentTime(): int
    {
        return $this->timer->getTimestamp();
    }

    public function setInterval(?DateInterval $interval): void
    {
        $this->timer->setInterval($interval);
    }
}
