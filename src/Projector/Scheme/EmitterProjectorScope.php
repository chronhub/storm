<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Reporter\DomainEvent;

final readonly class EmitterProjectorScope implements EmitterProjectorScopeInterface
{
    public function __construct(
        private SubscriptionManagement $management,
        private EmitterSubscriber $subscriber,
    ) {
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->subscriber->linkTo($streamName, $event);
    }

    public function emit(DomainEvent $event): void
    {
        $this->subscriber->emit($event);
    }

    public function stop(): void
    {
        $this->management->close();
    }

    public function streamName(): string
    {
        return $this->subscriber->subscription->currentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->subscriber->subscription->clock;
    }
}
