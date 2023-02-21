<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface MessageSubscriber extends Subscriber
{
    public function attachToReporter(MessageTracker $tracker): void;

    public function detachFromReporter(MessageTracker $tracker): void;
}
