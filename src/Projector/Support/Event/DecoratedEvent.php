<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Event;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use DateTimeImmutable;

abstract class DecoratedEvent
{
    protected function __construct(public readonly DomainEvent $event)
    {
    }

    abstract public static function fromEvent(DomainEvent $event): self;

    abstract public function id(): mixed;

    abstract public function time(): string|DateTimeImmutable;

    public function content(): array
    {
        return $this->event->toContent();
    }

    public function internalPosition(): int
    {
        return $this->event->header(EventHeader::INTERNAL_POSITION);
    }
}
