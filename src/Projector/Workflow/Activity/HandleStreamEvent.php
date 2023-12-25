<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Reporter\DomainEvent;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var callable{Subscriber,DomainEvent,int<0,max>}
     */
    private $eventProcessor;

    public function __construct(callable $eventProcessor)
    {
        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $streams = $subscriptor->pullStreamIterator();

        if (! $streams instanceof MergeStreamIterator) {
            return $next($subscriptor);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $subscriptor->setStreamName($streamName);

            $continue = ($this->eventProcessor)($subscriptor, $event, $position);

            if (! $continue || ! $subscriptor->isRunning()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($subscriptor);
    }
}
