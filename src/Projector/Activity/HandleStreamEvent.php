<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Subscription\Subscription;
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

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $streams = $subscription->pullStreamIterator();

        if (! $streams instanceof MergeStreamIterator) {
            return $next($subscription);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $subscription->setStreamName($streamName);

            $continue = ($this->eventProcessor)($subscription, $event, $position);

            if (! $continue || ! $subscription->sprint->inProgress()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($subscription);
    }
}
