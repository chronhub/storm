<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

final readonly class ProcessClosureEvent extends AbstractEventProcessor
{
    public function __construct(private Closure $eventHandlers)
    {
    }

    public function __invoke(Subscription $subscription, DomainEvent $event, int $key): bool
    {
        if (! $this->preProcess($subscription, $event, $key)) {
            return false;
        }

        $state = ($this->eventHandlers)($event, $subscription->state()->get());

        return $this->afterProcess($subscription, $state);
    }
}
