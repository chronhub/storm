<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use function usleep;

/**
 * @deprecated
 */
final class SleepDuration
{
    private int $counter = 0;

    public function __construct(
        private readonly int $sleep,
        private readonly int $maxIncrement
    ) {
    }

    public function increment(): void
    {
        $this->counter++;
    }

    public function reset(): void
    {
        $this->counter = 0;
    }

    public function sleep(): void
    {
        if ($this->sleep === 0) {
            return;
        }

        if ($this->counter >= $this->maxIncrement + 1) {
            $this->reset();
        }

        $sleepDuration = $this->calculateSleep();

        usleep($sleepDuration);
    }

    private function calculateSleep(): int
    {
        return $this->maxIncrement < 1 ? $this->sleep : $this->sleep * ($this->counter + 1);
    }
}
