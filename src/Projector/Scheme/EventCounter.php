<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

final class EventCounter
{
    private int $counter = 0;

    public function __construct(public readonly int $limit)
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }
    }

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
        return $this->counter === 0;
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
