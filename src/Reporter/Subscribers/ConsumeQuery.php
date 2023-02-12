<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Throwable;
use React\Promise\Deferred;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

final class ConsumeQuery implements MessageSubscriber
{
    use DetachMessageListener;

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, static function (MessageStory $story): void {
            $consumer = $story->consumers()->current();

            if ($consumer === null) {
                $story->markHandled(false);

                return;
            }

            $deferred = new Deferred();

            try {
                $consumer($story->message()->event(), $deferred);
            } catch (Throwable $exception) {
                $deferred->reject($exception);
            } finally {
                $story->withPromise($deferred->promise());

                $story->markHandled(true);
            }
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
