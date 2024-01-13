<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\OnDispatchPriority;

final class ConsumeEvent implements MessageSubscriber
{
    use DetachMessageListener;

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(
            static function (MessageStory $story): void {
                foreach ($story->consumers() as $consumer) {
                    $consumer($story->message()->event());
                }

                // Event handlers can be empty
                $story->markHandled(true);
            }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
