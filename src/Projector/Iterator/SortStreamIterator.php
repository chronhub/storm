<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use Iterator;
use function count;

final class SortStreamIterator implements Iterator
{
    use HasTimSort;

    protected array $iterators = [];

    public readonly int $numberOfIterators;

    public readonly array $originalIteratorOrder;

    public function __construct(array $streamNames, StreamEventIterator ...$iterators)
    {
        foreach ($iterators as $key => $iterator) {
            $this->iterators[$key][0] = $iterator;
            $this->iterators[$key][1] = $streamNames[$key];
        }

        $this->numberOfIterators = count($this->iterators);
        $this->originalIteratorOrder = $this->iterators;

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        foreach ($this->iterators as $iterator) {
            if ($iterator[0]->valid()) {
                return true;
            }
        }

        return false;
    }

    public function next(): void
    {
        // only advance the prioritized iterator
        $this->iterators[0][0]->next();

        $this->prioritizeIterators();
    }

    public function current(): mixed
    {
        return $this->iterators[0][0]->current();
    }

    public function streamName(): string
    {
        return $this->iterators[0][1];
    }

    public function key(): int
    {
        return $this->iterators[0][0]->key();
    }

    private function prioritizeIterators(): void
    {
        if ($this->numberOfIterators > 1) {
            $this->iterators = $this->originalIteratorOrder;

            $this->timSort($this->iterators, $this->numberOfIterators);
        }
    }
}
