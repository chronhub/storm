<?php

declare(strict_types=1);

namespace Chronhub\Storm\Stream;

use Generator;
use ArrayObject;
use Traversable;
use IteratorAggregate;
use Chronhub\Storm\Reporter\DomainEvent;
use function is_array;
use function iterator_count;
use function iterator_to_array;

final class Stream
{
    /**
     * @var iterable<DomainEvent>
     */
    private iterable $events;

    public function __construct(public readonly StreamName $streamName, iterable $events = [])
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

    public function name(): StreamName
    {
        return $this->streamName;
    }

    public function events(): Generator
    {
        yield from $this->events;

        return iterator_count($this->events);
    }
}
