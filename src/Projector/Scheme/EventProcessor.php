<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\PersistentSubscription;
use function in_array;
use function pcntl_signal_dispatch;

abstract readonly class EventProcessor
{
    final protected function preProcess(Subscription $subscription,
                                        DomainEvent $event,
                                        int $position,
                                        ?ProjectionRepository $repository): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        // set the current stream handled
        $streamName = $subscription->currentStreamName;

        // for a persistent projection, we check if position match our internal cache
        // if it does not, we return early to store what we have and sleep before the next run
        // and so on, till a gap is detected and provide retries
        if ($subscription instanceof PersistentSubscription) {
            if ($subscription->gap()->detect($streamName, $position, $event->header(Header::EVENT_TIME))) {
                return false;
            }
        }

        $subscription->streamPosition()->bind($streamName, $position);

        if ($subscription instanceof PersistentSubscription) {
            $subscription->eventCounter()->increment();
        }

        return true;
    }

    final protected function afterProcess(Subscription $subscription, ?array $state, ?ProjectionRepository $repository): bool
    {
        if ($state) {
            $subscription->state()->put($state);
        }

        if ($repository && $subscription instanceof PersistentSubscription) {
            $this->persistOnReachedCounter($subscription, $repository);
        }

        // keep running if projection has not been stopped
        return $subscription->sprint()->inProgress();
    }

    /**
     * Persist events when we hit the threshold
     *
     * @see ProjectionOption::BLOCK_SIZE
     */
    final protected function persistOnReachedCounter(PersistentSubscription $subscription, ProjectionRepository $projection): void
    {
        if ($subscription->eventCounter()->isReached()) {
            $projection->store();

            $subscription->eventCounter()->reset();

            $subscription->status = $projection->disclose();

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($subscription->status, $keepProjectionRunning, true)) {
                $subscription->sprint()->stop();
            }
        }
    }
}
