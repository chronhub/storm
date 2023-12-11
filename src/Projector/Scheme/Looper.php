<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use function array_merge;
use function microtime;

final class Looper
{
    private int $lap = 0;

    private array $laps = [];

    public function start(): void
    {
        $this->lap = 1;

        // todo : should be enabled by factory and/or per environment
        $this->initLap();
    }

    public function next(): void
    {
        $this->laps[$this->lap] = array_merge($this->laps[$this->lap], [microtime(true)]);

        $this->lap++;

        $this->initLap();
    }

    public function reset(): void
    {
        $this->lap = 0;

        $this->laps = [];
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

    public function laps(): array
    {
        return $this->laps;
    }

    private function initLap(): void
    {
        $this->laps[$this->lap] = [microtime(true)];
    }
}
