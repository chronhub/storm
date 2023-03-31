<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Closure;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use function array_keys;
use function array_values;

final readonly class HandleStreamEvent
{
    public function __construct(private Chronicler $chronicler,
                                private ?SubscriptionManagement $repository)
    {
    }

    public function __invoke(Subscription $subscription, Closure $next): callable|bool
    {
        $queryFilter = $subscription->context()->queryFilter();

        $streams = $this->retrieveStreams($subscription->streamPosition->all(), $queryFilter);

        $eventHandlers = $subscription->context()->eventHandlers();

        foreach ($streams as $eventPosition => $event) {
            $subscription->currentStreamName = $streams->streamName();

            $eventHandled = $eventHandlers($subscription, $event, $eventPosition, $this->repository);

            if (! $eventHandled || $subscription->runner->isStopped()) {
                return $next($subscription);
            }
        }

        return $next($subscription);
    }

    private function retrieveStreams(array $streamPositions, QueryFilter $queryFilter): SortStreamIterator
    {
        $iterator = [];

        foreach ($streamPositions as $streamName => $position) {
            if ($queryFilter instanceof ProjectionQueryFilter) {
                $queryFilter->setCurrentPosition($position + 1);
            }

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);

                $iterator[$streamName] = new StreamEventIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return new SortStreamIterator(array_keys($iterator), ...array_values($iterator));
    }
}
