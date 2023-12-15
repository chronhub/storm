<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

final class NoLoadedEventsMetrics
{
    public int $count = 1;

    public function increment(): void
    {
        $this->count++;
    }

    public function reset(): void
    {
        $this->count = 1;
    }

    public function calculateSleep(int $sleep, int $maxIncrement): int
    {
        if ($this->count > $maxIncrement) {
            $this->count = 1;
        }

        return $this->count * $sleep;
    }
}
