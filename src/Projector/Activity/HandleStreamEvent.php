<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;
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

    public function __invoke(Subscriber $subscriber, callable $next): callable|bool
    {
        $streams = $subscriber->pullStreamIterator();

        if (! $streams instanceof MergeStreamIterator) {
            return $next($subscriber);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $subscriber->setStreamName($streamName);

            $continue = ($this->eventProcessor)($subscriber, $event, $position);

            if (! $continue || ! $subscriber->sprint->inProgress()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($subscriber);
    }
}
