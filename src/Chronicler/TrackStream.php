<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Tracker\InteractWithTracker;

final class TrackStream implements StreamTracker
{
    use InteractWithTracker;

    public function newStory(string $eventName): StreamStory
    {
        return new EventDraft($eventName);
    }
}
