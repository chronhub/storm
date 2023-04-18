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

    public function __invoke(Subscription $subscription, Closure $next): callable|bool
    {
        $streams = $this->loadStreams->loadFrom($subscription);

        if ($this->eventProcessor === null) {
            $this->eventProcessor = $subscription->context()->eventHandlers();
        }

        foreach ($streams as $eventPosition => $event) {
            $subscription->currentStreamName = $streams->streamName();

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
