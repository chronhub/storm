<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Closure;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var callable
     */
    private $eventProcessor;

    public function __construct(
        private readonly LoadStreams $loadStreams,
        callable $eventProcessor
    ) {
        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        $streams = $this->getStreams($subscription);

        foreach ($streams as $position => $event) {
            $subscription->setCurrentStreamName($streams->streamName());

            $eventHandled = ($this->eventProcessor)($subscription, $event, $position);

            if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($subscription);
    }

    private function getStreams(Subscription $subscription): MergeStreamIterator
    {
        return $this->loadStreams->batch(
            $subscription->streamManager()->jsonSerialize(),
            $subscription->context()->queryFilter()
        );
    }
}
