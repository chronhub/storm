<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\Exceptions\MessageCollectedException;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Throwable;

final class TryConsumeEvent implements MessageSubscriber
{
    use DetachMessageListener;

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(
            static function (MessageStory $story): void {
                $exceptions = [];

                foreach ($story->consumers() as $consumer) {
                    try {
                        $consumer($story->message()->event());
                    } catch (Throwable $exception) {
                        $exceptions[] = $exception;
                    }
                }

                $story->markHandled(true);

                if ($exceptions !== []) {
                    $story->withRaisedException(
                        MessageCollectedException::fromExceptions(...$exceptions)
                    );
                }
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
