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

        foreach ($streams as $eventPosition => $event) {
            $subscription->setCurrentStreamName($streams->streamName());

            $eventHandled = $this->processEvent($subscription, $event, $eventPosition);

            if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                gc_collect_cycles();

                return $next($subscription);
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

    private function processEvent(Subscription $subscription, DomainEvent $event, int $eventPosition): bool
    {
        $eventProcessor = $this->reactors ?? $this->reactors = $subscription->context()->reactors();

        return $eventProcessor($subscription, $event, $eventPosition);
    }
}
