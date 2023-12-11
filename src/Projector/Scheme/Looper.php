<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use function array_merge;
use function microtime;

final class Looper
{
    private int $lap = 0;

    private array $laps = [];

    public function __construct(private readonly bool $useMetric = false)
    {
    }

    public function start(): void
    {
        $this->lap = 1;

        $this->initLap();
    }

    public function next(): void
    {
        if ($this->useMetric) {
            $this->laps[$this->lap] = array_merge($this->laps[$this->lap], [microtime(true)]);
        }

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

    /**
     * @return array<int, array<int, float>>
     */
    public function metrics(): array
    {
        return $this->laps;
    }

    private function initLap(): void
    {
        if ($this->useMetric) {
            $this->laps[$this->lap] = [microtime(true)];
        }
    }
}
