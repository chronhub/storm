<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

final class ConsumeCommand implements MessageSubscriber
{
    use DetachMessageListener;

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, static function (MessageStory $story): void {
            $messageHandler = $story->consumers()->current();

            if ($messageHandler) {
                $messageHandler($story->message()->event());
            }

            if (null !== $messageHandler || $story->message()->header(Header::EVENT_DISPATCHED) === true) {
                $story->markHandled(true);
            }
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
