<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\PersistentSubscription;

final readonly class ProcessArrayEvent extends EventProcessor
{
    public function __construct(private array $eventHandlers,
                                private ?MessageAlias $messageAlias = null)
    {
    }

    public function __invoke(Subscription $subscription, DomainEvent $event, int $key, ?ProjectionRepository $repository): bool
    {
        if (! $this->preProcess($subscription, $event, $key)) {
            return false;
        }

        if (null === $eventHandler = $this->determineEventHandler($event)) {
            if ($repository && $subscription instanceof PersistentSubscription) {
                $this->persistOnReachedCounter($subscription, $repository);
            }

            return $subscription->sprint()->inProgress();
        }

        $state = $eventHandler($event, $subscription->state()->get());

        return $this->afterProcess($subscription, $state, $repository);
    }

    private function determineEventHandler(DomainEvent $event): ?callable
    {
        if ($this->messageAlias) {
            return $this->eventHandlers[$this->messageAlias->classToAlias($event::class)];
        }

        return $this->eventHandlers[$event::class] ?? null;
    }
}
