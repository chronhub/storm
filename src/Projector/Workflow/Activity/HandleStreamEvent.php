<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\PullStreamIterator;
use Chronhub\Storm\Projector\Subscription\Notification\StreamProcessed;
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

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        $streams = $hub->listen(PullStreamIterator::class);

        if (! $streams instanceof MergeStreamIterator) {
            return $next($hub);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $hub->listen(StreamProcessed::class, $streamName);

            $continue = ($this->eventProcessor)($hub, $event, $position);

            if (! $continue || ! $hub->listen(IsSprintRunning::class)) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($hub);
    }
}
