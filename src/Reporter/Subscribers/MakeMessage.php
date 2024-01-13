<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\OnDispatchPriority;

final class MakeMessage implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(private readonly MessageFactory $messageFactory)
    {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(
            function (MessageStory $story): void {
                $message = ($this->messageFactory)($story->pullTransientMessage());

                $story->withMessage($message);
            }, OnDispatchPriority::MESSAGE_FACTORY->value);
    }
}
