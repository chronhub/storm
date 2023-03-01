<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use function in_array;
use function pcntl_signal_dispatch;

abstract class EventProcessor
{
    /**
     * Process before domain event is handled
     */
    final protected function preProcess(Context $context,
                                        DomainEvent $event,
                                        int $position,
                                        ?ProjectorRepository $repository): bool
    {
        if ($context->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        // set the current stream handled
        $streamName = $context->currentStreamName;

        // for a persistent projection, we check if position match our internal cache
        // if it does not, we return early to store what we have and sleep before the next run
        // and so on, till a gap is detected and provide retries
        if ($repository) {
            if ($context->gap->detect($streamName, $position, $event->header(Header::EVENT_TIME))) {
                return false;
            }
        }

        $context->streamPosition->bind($streamName, $position);

        if ($repository) {
            $context->eventCounter->increment();
        }

        return true;
    }

    /**
     * Process after domain event has been handled
     */
    final protected function afterProcess(Context $context, ?array $state, ?ProjectorRepository $repository): bool
    {
        if ($state) {
            $context->state->put($state);
        }

        if ($repository) {
            $this->persistOnReachedCounter($context, $repository);
        }

        // keep running if projection has not been stopped
        return ! $context->runner->isStopped();
    }

    /**
     * Persist events when we hit the threshold
     */
    final protected function persistOnReachedCounter(Context $context, ProjectorRepository $repository): void
    {
        if ($context->eventCounter->isReached()) {
            $repository->store();

            $context->eventCounter->reset();

            $context->status = $repository->disclose();

            $keepProjectionRunning = [ProjectionStatus::RUNNING, ProjectionStatus::IDLE];

            if (! in_array($context->status, $keepProjectionRunning, true)) {
                $context->runner->stop(true);
            }
        }
    }
}
