<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Projector\Workflow\Timer;

readonly class TimeWatcher
{
    public function __construct(protected Timer $timer)
    {
    }

    public function start(): void
    {
        $this->timer->start();
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

    public function getElapsedTime(): int
    {
        return $this->timer->getElapsedTime();
    }
}
