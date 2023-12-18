<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterManagement;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Reporter\DomainEvent;

final readonly class EmitterAccess implements EmitterScope
{
    public function __construct(private EmitterManagement $emitter)
    {
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->emitter->linkTo($streamName, $event);
    }

    public function emit(DomainEvent $event): void
    {
        $this->emitter->emit($event);
    }

    public function stop(): void
    {
        $this->emitter->close();
    }

    public function streamName(): string
    {
        return $this->emitter->getCurrentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->emitter->getClock();
    }
}
