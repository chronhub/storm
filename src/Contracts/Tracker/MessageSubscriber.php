<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface MessageSubscriber extends Subscriber
{
    /**
     * Subscribe to the message tracker
     */
    public function attachToReporter(MessageTracker $tracker): void;

    /**
     * Unsubscribe from message tracker
     */
    public function detachFromReporter(MessageTracker $tracker): void;
}
