<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use function in_array;
use function pcntl_signal_dispatch;

abstract readonly class EventProcessor
{
    final protected function preProcess(Subscription $subscription,
                                        DomainEvent $event,
                                        int $position,
                                        ?SubscriptionManagement $repository): bool
    {
        if ($subscription->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        // set the current stream handled
        $streamName = $subscription->currentStreamName;

        // for a persistent projection, we check if position match our internal cache
        // if it does not, we return early to store what we have and sleep before the next run
        // and so on, till a gap is detected and provide retries
        if ($repository) {
            if ($subscription->gap->detect($streamName, $position, $event->header(Header::EVENT_TIME))) {
                return false;
            }
        }

        $subscription->streamPosition->bind($streamName, $position);

        if ($repository) {
            $subscription->eventCounter->increment();
        }

        return true;
    }

    final protected function afterProcess(Subscription $subscription, ?array $state, ?SubscriptionManagement $repository): bool
    {
        if ($state) {
            $subscription->state->put($state);
        }

        if ($repository) {
            $this->persistOnReachedCounter($subscription, $repository);
        }

        // keep running if projection has not been stopped
        return ! $subscription->runner->isStopped();
    }

    /**
     * Persist events when we hit the threshold
     */
    final protected function persistOnReachedCounter(Subscription $subscription, SubscriptionManagement $repository): void
    {
        if ($subscription->eventCounter->isReached()) {
            $repository->store();

            $subscription->eventCounter->reset();

            $subscription->status = $repository->disclose();

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($subscription->status, $keepProjectionRunning, true)) {
                $subscription->runner->stop(true);
            }
        }
    }
}
