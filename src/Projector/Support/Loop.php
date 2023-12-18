<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support;

final class Loop
{
    private int $loop = 0;

    public function start(): void
    {
        $this->loop = 1;
    }

    public function next(): void
    {
        $this->loop++;
    }

    public function reset(): void
    {
        $this->loop = 0;
    }

    public function loop(): int
    {
        return $this->loop;
    }

    public function isFirstLoop(): bool
    {
        return $this->loop === 1;
    }

    public function hasStarted(): bool
    {
        return $this->loop > 0;
    }
}
