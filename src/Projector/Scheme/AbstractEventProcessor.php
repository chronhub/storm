<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Reporter\DomainEvent;
use function in_array;
use function pcntl_signal_dispatch;

abstract readonly class AbstractEventProcessor
{
    final protected function preProcess(Subscription $subscription,
                                        DomainEvent $event,
                                        int $position): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        // set the current stream handled by reference
        $streamName = $subscription->currentStreamName;

        // for a persistent projection, we check if position match our internal cache
        // if it does not, we return early to store what we have and sleep before the next run
        // and so on, till a gap is detected and provide retries
        if ($subscription instanceof PersistentSubscriptionInterface) {
            if ($subscription->gap()->detect($streamName, $position, $event->header(Header::EVENT_TIME))) {
                return false;
            }
        }

        $subscription->streamPosition()->bind($streamName, $position);

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
            $this->persistWhenCounterIsReached($subscription);
        }

        return $subscription->sprint()->inProgress();
    }

    /**
     * Persist events when we hit the threshold
     *
     * @see ProjectionOption::BLOCK_SIZE
     */
    final protected function persistWhenCounterIsReached(PersistentSubscriptionInterface $subscription): void
    {
        if ($subscription->eventCounter()->isReached()) {
            $subscription->store();

            $subscription->eventCounter()->reset();

            $subscription->setStatus($subscription->disclose());

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($subscription->currentStatus(), $keepProjectionRunning, true)) {
                $subscription->sprint()->stop();
            }
        }
    }
}
