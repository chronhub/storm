<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use function is_callable;

final readonly class ProcessArrayEvent extends AbstractEventProcessor
{
    public function __construct(
        private array $eventHandlers,
        private ?MessageAlias $messageAlias = null
    ) {
    }

    public function __invoke(Subscription $subscription, DomainEvent $event, int $key): bool
    {
        if (! $this->preProcess($subscription, $event, $key)) {
            return false;
        }

        $eventHandler = $this->determineEventHandler($event);

        if (! is_callable($eventHandler)) {
            if ($subscription instanceof PersistentSubscriptionInterface) {
                $this->persistWhenCounterIsReached($subscription);
            }

            return $subscription->sprint()->inProgress();
        }

        $state = $eventHandler($event, $subscription->state()->get());

        return $this->afterProcess($subscription, $state);
    }

    private function determineEventHandler(DomainEvent $event): ?callable
    {
        $eventClass = $event::class;

        if ($this->messageAlias) {
            $eventClass = $this->messageAlias->classToAlias($eventClass);
        }

        return $this->eventHandlers[$eventClass] ?? null;
    }
}
