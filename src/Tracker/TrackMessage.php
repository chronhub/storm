<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;

final class TrackMessage implements MessageTracker
{
    use InteractWithTracker;

    public function onDispatch(callable $story, int $priority = 0): Listener
    {
        return $this->watch(Reporter::DISPATCH_EVENT, $story, $priority);
    }

    public function onFinalize(callable $story, int $priority = 0): Listener
    {
        return $this->watch(Reporter::FINALIZE_EVENT, $story, $priority);
    }

    public function newStory(string $eventName): MessageStory
    {
        return new Draft($eventName);
    }
}
