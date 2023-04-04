<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\OnDispatchPriority;

final class DecorateMessage implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(private readonly MessageDecorator $messageDecorator)
    {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, function (MessageStory $story): void {
            $message = $this->messageDecorator->decorate($story->message());

            $story->withMessage($message);
        }, OnDispatchPriority::MESSAGE_DECORATOR->value);
    }
}
