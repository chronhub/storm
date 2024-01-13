<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use ArrayIterator;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Countable;
use Generator;
use Iterator;

use function iterator_to_array;

final class StreamIterator implements Countable, Iterator
{
    private ?DomainEvent $event = null;

    private ?int $position = null;

    /**
     * @var ArrayIterator<DomainEvent>
     */
    private ArrayIterator $streamEvents;

    /**
     * Stream events must already sorted by ascending event time
     * todo test if sorted ?again by event time, which cost in performance
     */
    public function __construct(Generator $streamEvents)
    {
        $this->streamEvents = new ArrayIterator(iterator_to_array($streamEvents));

        $this->next();
    }

    public function current(): ?DomainEvent
    {
        return $this->event;
    }

    public function next(): void
    {
        $this->event = $this->streamEvents->current();

        if ($this->event instanceof DomainEvent) {
            $this->position = (int) $this->event->header(EventHeader::INTERNAL_POSITION);
        } else {
            $this->position = null;
            $this->event = null;
        }

        $this->streamEvents->next();
    }

    public function key(): ?int
    {
        return $this->position;
    }

    public function rewind(): void
    {
        $this->streamEvents->rewind();

        $this->next();
    }

    public function valid(): bool
    {
        return $this->event instanceof DomainEvent;
    }

    public function count(): int
    {
        return $this->streamEvents->count();
    }
}
