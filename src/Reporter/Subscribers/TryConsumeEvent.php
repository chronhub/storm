<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\Exceptions\MessageCollectedException;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Throwable;
use function count;

final class TryConsumeEvent implements MessageSubscriber
{
    use DetachMessageListener;

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, static function (MessageStory $story): void {
            $exceptions = [];

            foreach ($story->consumers() as $consumer) {
                try {
                    $consumer($story->message()->event());
                } catch (Throwable $exception) {
                    $exceptions[] = $exception;
                }
            }

            $story->markHandled(true);

            if (count($exceptions) > 0) {
                $story->withRaisedException(
                    MessageCollectedException::fromExceptions(...$exceptions)
                );
            }
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
