<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use function array_keys;
use function array_values;

final readonly class HandleStreamEvent
{
    public function __construct(private Chronicler $chronicler,
                                private ?ProjectorRepository $repository)
    {
    }

    public function __invoke(Context $context, Closure $next): callable|bool
    {
        $streams = $this->retrieveStreams($context->streamPosition->all(), $context->queryFilter());

        $eventHandlers = $context->eventHandlers();

        foreach ($streams as $eventPosition => $event) {
            $context->currentStreamName = $streams->streamName();

            $eventHandled = $eventHandlers($context, $event, $eventPosition, $this->repository);

            if (! $eventHandled || $context->runner->isStopped()) {
                return $next($context);
            }
        }

        return $next($context);
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
