<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Event;

use Chronhub\Storm\Reporter\DomainEvent;
use DateTimeImmutable;

abstract class DecoratedEvent
{
    protected function __construct(public readonly DomainEvent $event)
    {
    }

    abstract public static function fromEvent(DomainEvent $event): self;

    abstract public function eventId(): mixed;

    abstract public function eventTime(): string|DateTimeImmutable;

    public function eventContent(): array
    {
        return $this->event->toContent();
    }
}
