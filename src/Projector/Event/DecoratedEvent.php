<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Event;

use Chronhub\Storm\Reporter\DomainEvent;
use DateTimeImmutable;

use function get_class;

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

    public function class(): string
    {
        return get_class($this->event);
    }
}
