<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use ArrayObject;
use Countable;
use IteratorAggregate;
use Traversable;

use function is_array;
use function iterator_count;
use function iterator_to_array;

final class StreamEvents implements Countable, IteratorAggregate
{
    private IteratorAggregate $events;

    public function __construct(iterable $events = [])
    {
        if ($events instanceof IteratorAggregate) {
            $this->events = $events;
        } elseif (is_array($events)) {
            $this->events = new ArrayObject($events);
        } else {
            $this->events = new class($events) implements IteratorAggregate
            {
                private ArrayObject $cachedIterator;

                public function __construct(public readonly Traversable $events)
                {
                }

                public function getIterator(): Traversable
                {
                    return $this->cachedIterator ??= new ArrayObject(iterator_to_array($this->events, false));
                }
            };
        }
    }

    public function getIterator(): IteratorAggregate
    {
        return $this->events;
    }

    public function count(): int
    {
        return iterator_count($this->events);
    }
}
