<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Management\EventEmitted;
use Chronhub\Storm\Projector\Support\Notification\Management\EventLinkedTo;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionClosed;
use Chronhub\Storm\Projector\Support\Notification\Stream\CurrentProcessedStream;
use Chronhub\Storm\Reporter\DomainEvent;

final class EmitterAccess implements ArrayAccess, EmitterScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly NotificationHub $hub,
        private readonly SystemClock $clock
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $this->hub->trigger(new EventEmitted($event));
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->hub->trigger(new EventLinkedTo($streamName, $event));
    }

    public function stop(): void
    {
        $this->hub->trigger(new ProjectionClosed());
    }

    public function streamName(): string
    {
        return $this->hub->expect(CurrentProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
