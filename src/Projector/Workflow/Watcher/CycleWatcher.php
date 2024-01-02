<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

class CycleWatcher
{
    protected int $cycle = 0;

    public function start(): void
    {
        $this->cycle = 1;
    }

    public function next(): void
    {
        $this->cycle++;
    }

    public function reset(): void
    {
        $this->cycle = 0;
    }

    public function cycle(): int
    {
        return $this->cycle;
    }

    public function isFirstCycle(): bool
    {
        return $this->cycle === 1;
    }

    public function hasStarted(): bool
    {
        return $this->cycle > 0;
    }
}
