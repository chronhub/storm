<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionManagement;
use Chronhub\Storm\Reporter\DomainEvent;

final readonly class EmitterProjectorScope implements EmitterProjectorScopeInterface
{
    public function __construct(private EmitterSubscriptionManagement $emitter)
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
