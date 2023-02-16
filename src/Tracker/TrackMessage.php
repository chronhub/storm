<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;

final class TrackMessage implements MessageTracker
{
    use InteractWithTracker;

    public function newStory(string $eventName): MessageStory
    {
        return new Draft($eventName);
    }
}
