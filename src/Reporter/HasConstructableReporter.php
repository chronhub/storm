<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\Exceptions\MessageNotHandled;
use Throwable;

trait HasConstructableReporter
{
    public function __construct(public readonly MessageTracker $tracker)
    {
    }

    public function subscribe(MessageSubscriber ...$messageSubscribers): void
    {
        foreach ($messageSubscribers as $messageSubscriber) {
            $messageSubscriber->attachToReporter($this->tracker);
        }
    }

    public function tracker(): MessageTracker
    {
        return $this->tracker;
    }

    /**
     * Relay message
     */
    protected function relayMessage(MessageStory $story): void
    {
        try {
            $this->tracker->disclose($story);

            if (! $story->isHandled()) {
                $messageName = $story->message()->header(Header::EVENT_TYPE) ?? $story->message()->event()::class;

                throw MessageNotHandled::withMessageName($messageName);
            }
        } catch (Throwable $exception) {
            $story->withRaisedException($exception);
        } finally {
            $story->stop(false);

            $story->withEvent(Reporter::FINALIZE_EVENT);

            $this->tracker->disclose($story);
        }
    }
}
