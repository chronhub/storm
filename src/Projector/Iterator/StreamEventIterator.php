<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Generator;
use Iterator;

final class StreamEventIterator implements Iterator
{
    private ?DomainEvent $event = null;

    private int|false $position = 0;

    public function __construct(private readonly Generator $streamEvents)
    {
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
            $position = (int) $this->event->header(EventHeader::INTERNAL_POSITION);

            $this->position = $position;
        } else {
            $this->position = false;
            $this->event = null;
        }

        $this->streamEvents->next();
    }

    public function key(): false|int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->event !== null;
    }

    public function rewind(): void
    {
    }
}
