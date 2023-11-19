<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;

use function pcntl_signal_dispatch;

abstract readonly class AbstractEventProcessor
{
    final protected function preProcess(Subscription $subscription, DomainEvent $event, int $position): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        $streamName = $subscription->currentStreamName();
        $eventTime = $subscription instanceof PersistentSubscriptionInterface ? $event->header(Header::EVENT_TIME) : false;

        // only bind stream name to his position if no gap and no retry left
        $isBound = $subscription->streamManager()->bind($streamName, $position, $eventTime);

        if (! $isBound) {
            return false;
        }

        // increment event counter only if stream is bound and subscription is persistent
        if ($eventTime !== false) {
            /** @var PersistentSubscriptionInterface $subscription */
            $subscription->eventCounter()->increment();
        }

        return true;
    }

    final protected function afterProcess(Subscription $subscription, ?array $state): bool
    {
        if ($state) {
            $subscription->state()->put($state);
        }

        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->persistWhenThresholdIsReached();
        }

        return $subscription->sprint()->inProgress();
    }
}
