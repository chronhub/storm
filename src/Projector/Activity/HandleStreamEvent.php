<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Subscription\Beacon;
use Chronhub\Storm\Reporter\DomainEvent;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var callable{Beacon,DomainEvent,int<0,max>}
     */
    private $eventProcessor;

    public function __construct(callable $eventProcessor)
    {
        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        $streams = $manager->pullStreamIterator();

        if (! $streams instanceof MergeStreamIterator) {
            return $next($manager);
        }

        foreach ($streams as $position => $event) {
            $streamName = $streams->streamName();

            $manager->setStreamName($streamName);

            $continue = ($this->eventProcessor)($manager, $event, $position);

            if (! $continue || ! $manager->sprint->inProgress()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($manager);
    }
}
