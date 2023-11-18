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
    final protected function preProcess(
        Subscription $subscription,
        DomainEvent $event,
        int $position): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        // set the current stream handled by reference
        $streamName = $subscription->currentStreamName();

        // for a persistent projection, we check if position match our internal cache
        // if it does not, we return early to store what we have and sleep before the next run
        // and so on, till a gap is detected and provide retries
        if ($subscription instanceof PersistentSubscriptionInterface) {
            if ($subscription->streamManager()->detectGap($streamName, $position, $event->header(Header::EVENT_TIME))) {
                return false;
            }
        }

        $subscription->streamManager()->bind($streamName, $position);

        if ($subscription instanceof PersistentSubscriptionInterface) {
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
