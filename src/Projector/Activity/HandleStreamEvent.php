<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function gc_collect_cycles;

final readonly class HandleStreamEvent
{
    public function __construct(private LoadStreams $loadStreams)
    {
    }

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        $streams = $this->loadStreams->batch(
            $subscription->streamManager()->jsonSerialize(),
            $subscription->context()->queryFilter()
        );

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

    private function processEvent(Subscription $subscription, DomainEvent $event, int $eventPosition): bool
    {
        $eventProcessor = $subscription->context()->eventHandlers();

        return $eventProcessor($subscription, $event, $eventPosition);
    }
}
