<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Closure;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /**
     * @var null|callable
     */
    private $eventProcessor;

    public function __construct(private readonly LoadStreams $loadStreams)
    {
    }

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        $streams = $this->loadStreams->batch(
            $subscription->streamPosition()->all(),
            $subscription->context()->queryFilter()
        );

        if ($this->eventProcessor === null) {
            $this->eventProcessor = $subscription->context()->eventHandlers();
        }

        foreach ($streams as $eventPosition => $event) {
            $subscription->setCurrentStreamName($streams->streamName());

            $eventHandled = ($this->eventProcessor)($subscription, $event, $eventPosition);

            if (! $eventHandled || ! $subscription->sprint()->inProgress()) {
                gc_collect_cycles();

                return $next($subscription);
            }
        }

        gc_collect_cycles();

        return $next($subscription);
    }
}
