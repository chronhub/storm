<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

final class Looper
{
    private int $lap = 0;

    public function start(): void
    {
        $this->lap = 1;
    }

    public function next(): void
    {
        $this->lap++;
    }

    public function reset(): void
    {
        $this->lap = 0;
    }

    public function lap(): int
    {
        return $this->lap;
    }

    public function isFirstLap(): bool
    {
        return $this->lap === 1;
    }

    public function hasStarted(): bool
    {
        return $this->lap > 0;
    }
}
