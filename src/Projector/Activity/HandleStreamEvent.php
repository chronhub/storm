<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use Chronhub\Storm\Stream\StreamName;
use Closure;
use function array_keys;
use function array_values;
use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var null|callable
     */
    private $eventProcessor = null;

    public function __construct(
        private readonly Chronicler $chronicler,
        private readonly ?ProjectionManagement $repository
    ) {
    }

    public function __invoke(Subscription $subscription, Closure $next): callable|bool
    {
        $streams = $this->retrieveStreams(
            $subscription->streamPosition()->all(),
            $subscription->context()->queryFilter()
        );

        if ($this->eventProcessor === null) {
            $this->eventProcessor = $subscription->context()->eventHandlers();
        }

        foreach ($streams as $eventPosition => $event) {
            $subscription->currentStreamName = $streams->streamName();

            $eventHandled = ($this->eventProcessor)($subscription, $event, $eventPosition, $this->repository);

            if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                gc_collect_cycles();

                return $next($subscription);
            }
        }

        gc_collect_cycles();

        return $next($subscription);
    }

    private function retrieveStreams(array $streamPositions, QueryFilter $queryFilter): SortStreamIterator
    {
        $streams = [];

        foreach ($streamPositions as $streamName => $position) {
            if ($queryFilter instanceof ProjectionQueryFilter) {
                $queryFilter->setCurrentPosition($position + 1);
            }

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);

                $streams[$streamName] = new StreamEventIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return new SortStreamIterator(array_keys($streams), ...array_values($streams));
    }
}
