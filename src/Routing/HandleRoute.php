<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Chronhub\Storm\Contracts\Producer\MessageProducer;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;
use Chronhub\Storm\Contracts\Routing\RouteLocator;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\OnDispatchPriority;

use function is_array;

final class HandleRoute implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(
        private readonly RouteLocator $routeLocator,
        private readonly MessageProducer $messageProducer,
        private readonly ProducerUnity $producerUnity
    ) {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(
            function (MessageStory $story): void {
                $message = $story->message();

                $isSync = $this->producerUnity->isSync($message);

                if (! $isSync) {
                    $queueOptions = $this->routeLocator->onQueue($message);

                    if (is_array($queueOptions) && $queueOptions !== []) {
                        $message = $message->withHeader('queue', $queueOptions);
                    }
                }

                $dispatchedMessage = $this->messageProducer->produce($message);

                $story->withMessage($dispatchedMessage);

                if ($isSync) {
                    $story->withConsumers($this->routeLocator->route($dispatchedMessage));
                }
            }, OnDispatchPriority::ROUTE->value);
    }
}
