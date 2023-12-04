<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Reporter\DomainEvent;

use function gc_collect_cycles;
use function is_callable;

final class HandleStreamEvent
{
    /**
     * @var callable{<Subscription,DomainEvent,int<1,max>>}|EventProcessor
     */
    private $eventProcessor;

    public function __construct($eventProcessor)
    {
        if (! is_callable($eventProcessor)) {
            throw new InvalidArgumentException('Event processor must be callable');
        }

        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $streams = $subscription->pullStreamIterator();

        if ($streams instanceof MergeStreamIterator) {
            foreach ($streams as $position => $event) {
                $streamName = $streams->streamName();

                $subscription->setStreamName($streamName);

                $eventHandled = ($this->eventProcessor)($subscription, $event, $position);

                if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                    break;
                }
            }

            gc_collect_cycles();
        }

        return $next($subscription);
    }
}
