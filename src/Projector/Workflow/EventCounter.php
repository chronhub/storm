<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

class EventCounter
{
    /**
     * @var int<0,max>
     */
    private int $counter = 0;

    /**
     * @param positive-int $limit
     */
    public function __construct(public readonly int $limit)
    {
        /** @phpstan-ignore-next-line  */
        if ($limit < 1) {
            throw new InvalidArgumentException('Event counter limit must be greater than 0');
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
        return $this->counter >= $this->limit;
    }

    public function count(): int
    {
        return $this->counter;
    }
}
