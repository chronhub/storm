<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\TokenBucket;

final class NoStreamLoadedCounter
{
    private int $counter;

    public function __construct(private readonly TokenBucket $consumer)
    {
        $this->reset();

        // overflow the bucket to sleep for every increment
        $this->consumer->consume($this->consumer->getCapacity());
    }

    public function hasLoadedStreams(bool $hasLoadedStreams): void
    {
        $hasLoadedStreams ? $this->reset() : $this->counter++;
    }

    public function reset(): void
    {
        $this->counter = 0;
    }

    public function sleep(): void
    {
        dump('count '.$this->counter);

        match ($this->counter) {
            0 => $this->consumeWhenNoIncrement(),
            1 => $this->consumeCapacity(),
            default => $this->consumeCounter(),
        };

        if ($this->counter >= $this->consumer->getCapacity()) {
            $this->reset();
        }
    }

    /**
     * Happens when a "real" reset of event counter is done,
     * and increment was never called, or this counter was reset,
     * so we sleep once and reset the counter.
     * checkMe: It can be an issue when the first sleep can be very long depends on the parameters.
     */
    private function consumeWhenNoIncrement(): void
    {
        $this->consumer->consume();

        $this->reset();
    }

    private function consumeCapacity(): void
    {
        $this->consumer->consume();
    }

    private function consumeCounter(): void
    {
        $this->consumer->consume($this->counter);
    }
}
