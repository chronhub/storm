<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

class Stats
{
    private int $cycles = 0;

    public function inc(): void
    {
        $this->cycles++;
    }

    public function reset(): void
    {
        $this->cycles = 0;
    }

    public function hasStarted(): bool
    {
        return $this->cycles > 0;
    }

    public function cycles(): int
    {
        return $this->cycles;
    }
}
