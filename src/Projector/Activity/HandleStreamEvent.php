<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Scheme\EventProcessor;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    private ?EventProcessor $eventProcessor;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $streams = $subscription->pullStreamIterator();

        if ($streams instanceof MergeStreamIterator) {
            $eventProcessor = $this->eventProcessor($subscription);

            foreach ($streams as $position => $event) {
                $streamName = $streams->streamName();

                $subscription->setStreamName($streamName);

                $eventHandled = $eventProcessor($subscription, $event, $position);

                if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                    break;
                }
            }

            gc_collect_cycles();
        }

        return $next($subscription);
    }

    private function eventProcessor(Subscription $subscription): callable
    {
        return $this->eventProcessor ??= new EventProcessor(
            $subscription->context()->reactors(),
            $subscription->scope()
        );
    }
}
