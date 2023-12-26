<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Subscription\Notification;
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

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        $streams = $notification->pullStreams();

        if (! $streams instanceof MergeStreamIterator) {
            return $next($notification);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $notification->onStreamProcess($streamName);

            $continue = ($this->eventProcessor)($notification, $event, $position);

            if (! $continue || ! $notification->isRunning()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($notification);
    }
}
