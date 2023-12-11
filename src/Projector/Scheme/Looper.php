<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use function array_merge;
use function microtime;

final class Looper
{
    private int $lap = 0;

    private array $cycles = [];

    public function __construct()
    {
    }

    public function start(): void
    {
        $this->lap = 1;
        $this->cycles[$this->lap] = [microtime(true)];
    }

    public function next(): void
    {
        $this->cycles[$this->lap] = array_merge($this->cycles[$this->lap], [microtime(true)]);
        $this->lap++;
    }

    public function reset(): void
    {
        $this->lap = 0;
        $this->cycles = [];
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

    public function cycles(): array
    {
        return $this->cycles;
    }
}
