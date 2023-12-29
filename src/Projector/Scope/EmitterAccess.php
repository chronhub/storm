<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Hook\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Hook\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectProcessedStream;
use Chronhub\Storm\Reporter\DomainEvent;

final class EmitterAccess implements ArrayAccess, EmitterScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly HookHub $hub,
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
        return $this->hub->expect(ExpectProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
