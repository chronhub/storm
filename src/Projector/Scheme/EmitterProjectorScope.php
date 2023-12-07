<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Reporter\DomainEvent;

final readonly class EmitterProjectorScope implements EmitterProjectorScopeInterface
{
    public function __construct(private EmitterSubscriptionInterface $subscription)
    {
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->subscription->linkTo($streamName, $event);
    }

    public function emit(DomainEvent $event): void
    {
        $this->subscription->emit($event);
    }

    public function stop(): void
    {
        $this->subscription->close();
    }

    public function streamName(): string
    {
        return $this->subscription->currentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->subscription->clock();
    }
}
