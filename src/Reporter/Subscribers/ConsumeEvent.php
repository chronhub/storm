<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

final class ConsumeEvent implements MessageSubscriber
{
    use DetachMessageListener;

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, static function (MessageStory $story): void {
            foreach ($story->consumers() as $consumer) {
                $consumer($story->message()->event());
            }

            // Event handlers can be empty
            $story->markHandled(true);
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
