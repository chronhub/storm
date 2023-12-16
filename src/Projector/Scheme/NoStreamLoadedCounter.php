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
        match ($this->counter) {
            0 => $this->consumeWhenNoIncrement(),
            1 => $this->consumeCapacity(),
            default => $this->consumeCounter(),
        };

        dump('count '.$this->counter);

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
        $this->consumeCapacity();

        $this->reset();
    }

    /**
     * with a capacity of 5, it would produce two requests without sleeping (1+2<5),
     * so, we fetch all tokens available on the first query,
     * to actually sleep and share the sleeping time between five queries.
     */
    private function consumeCapacity(): void
    {
        $this->consumer->consume($this->consumer->getCapacity());
    }

    /**
     * Force sleep per query till the capacity is reached
     */
    private function consumeCounter(): void
    {
        $this->consumer->consume($this->counter);
    }
}
