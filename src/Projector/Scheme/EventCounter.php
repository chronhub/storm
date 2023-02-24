<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

class EventCounter
{
    public function __construct(public readonly int $limit)
    {
    }

    protected int $counter = 0;

    public function increment(): void
    {
        $this->counter++;
    }

    public function reset(): void
    {
        $this->counter = 0;
    }

    public function isReset(): bool
    {
        return 0 === $this->counter;
    }

    public function isReached(): bool
    {
        return $this->counter === $this->limit;
    }

    public function current(): int
    {
        return $this->counter;
    }
}
