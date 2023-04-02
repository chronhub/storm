<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Closure;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;

final readonly class ProcessClosureEvent extends EventProcessor
{
    public function __construct(private Closure $eventHandlers)
    {
    }

    public function __invoke(Subscription $subscription, DomainEvent $event, int $key, ?ProjectionRepositoryInterface $repository): bool
    {
        if (! $this->preProcess($subscription, $event, $key)) {
            return false;
        }

        $state = ($this->eventHandlers)($event, $subscription->state()->get());

        return $this->afterProcess($subscription, $state, $repository);
    }
}
