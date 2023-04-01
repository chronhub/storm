<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Iterator;

use Iterator;
use Generator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;

final class StreamEventIterator implements Iterator
{
    private ?DomainEvent $currentEvent = null;

    private int|false $currentPosition = 0;

    public function __construct(private readonly Generator $eventStreams)
    {
        $this->next();
    }

    public function current(): ?DomainEvent
    {
        return $this->currentEvent;
    }

    public function next(): void
    {
        $this->currentEvent = $this->eventStreams->current();

        if ($this->currentEvent instanceof DomainEvent) {
            $position = (int) $this->currentEvent->header(EventHeader::INTERNAL_POSITION);

            $this->currentPosition = $position;
        } else {
            $this->currentPosition = false;
            $this->currentEvent = null;
        }

        $this->eventStreams->next();
    }

    public function key(): false|int
    {
        return $this->currentPosition;
    }

    public function valid(): bool
    {
        return $this->currentEvent !== null;
    }

    public function rewind(): void
    {
    }
}
