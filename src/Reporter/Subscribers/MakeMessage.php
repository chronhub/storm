<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

final class MakeMessage implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(private readonly MessageFactory $messageFactory)
    {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, function (MessageStory $story): void {
            $message = ($this->messageFactory)($story->pullTransientMessage());

            $story->withMessage($message);
        }, OnDispatchPriority::MESSAGE_FACTORY->value);
    }
}
