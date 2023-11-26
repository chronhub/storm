<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var callable|null
     */
    private $reactors = null;

    public function __construct(private readonly LoadStreams $loadStreams)
    {
    }

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        $streams = $this->getStreams($subscription);

        // add observer ['stream_name' => ['loaded' => $streams->count(), 'handled' => 0]]

        foreach ($streams as $position => $event) {
            $subscription->setCurrentStreamName($streams->streamName());

            $eventHandled = $this->processEvent($subscription, $event, $position);

            // add observer ['stream_name' => ['handled' => $handled + 1]]

            if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                break;
            }
        }

        gc_collect_cycles();

        return $next($subscription);
    }

    private function processEvent(Subscription $subscription, DomainEvent $event, int $position): bool
    {
        $eventProcessor = $this->reactors ?? $this->reactors = $subscription->context()->reactors();

        return $eventProcessor($subscription, $event, $position);
    }

    private function getStreams(Subscription $subscription): MergeStreamIterator
    {
        return $this->loadStreams->batch(
            $subscription->streamManager()->jsonSerialize(),
            $subscription->context()->queryFilter()
        );
    }
}
