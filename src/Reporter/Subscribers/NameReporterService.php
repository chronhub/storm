<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Subscribers;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\OnDispatchPriority;

final class NameReporterService implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(private readonly string $serviceId)
    {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(function (MessageStory $story): void {
            $message = $story->message();

            if ($message->hasNot(Header::REPORTER_ID)) {
                $story->withMessage(
                    $message->withHeader(Header::REPORTER_ID, $this->serviceId)
                );
            }
        }, OnDispatchPriority::MESSAGE_FACTORY->value - 1);
    }
}
