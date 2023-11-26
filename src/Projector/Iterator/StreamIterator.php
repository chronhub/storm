<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use ArrayIterator;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
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
     * Stream events should be already sorted by event time in ascending order
     * todo test if sorted ?again by event time has cost in performance
     */
    public function __construct(Generator $streamEvents)
    {
        $this->streamEvents = new ArrayIterator(iterator_to_array($streamEvents));

        // Empty generator should throw stream not found exception
        // we make sure that stream is not empty as MergeStreamIterator
        // does not handle empty stream events
        if ($this->streamEvents->count() === 0) {
            throw new StreamNotFound('Stream not found');
        }

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

    public function count(): int
    {
        return $this->streamEvents->count();
    }

    public function valid(): bool
    {
        return $this->event instanceof DomainEvent;
    }
}
