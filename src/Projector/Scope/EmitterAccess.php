<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Observer\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Observer\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Reporter\DomainEvent;

final class EmitterAccess implements ArrayAccess, EmitterScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly Notification $notification,
        private readonly SystemClock $clock
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $this->notification->dispatch(new EventEmitted($event));
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->notification->dispatch(new EventLinkedTo($streamName, $event));
    }

    public function stop(): void
    {
        $this->notification->dispatch((new ProjectionClosed()));
    }

    public function streamName(): string
    {
        return $this->notification->observeStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
