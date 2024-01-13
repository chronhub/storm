<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

class BatchCounterWatcher
{
    /**
     * @var int<0,max>
     */
    protected int $perBatchCount = 0;

    /**
     * @param positive-int $limit
     */
    public function __construct(public readonly int $limit)
    {
        /** @phpstan-ignore-next-line */
        if ($limit < 1) {
            throw new InvalidArgumentException('Batch counter limit must be greater than 0');
        }
    }

    public function increment(): void
    {
        $this->perBatchCount++;
    }

    public function reset(): void
    {
        $this->perBatchCount = 0;
    }

    public function isReset(): bool
    {
        return $this->perBatchCount === 0;
    }

    public function isReached(): bool
    {
        return $this->perBatchCount >= $this->limit;
    }

    public function count(): int
    {
        return $this->perBatchCount;
    }
}
