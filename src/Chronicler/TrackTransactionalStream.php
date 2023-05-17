<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;
use Chronhub\Storm\Tracker\InteractWithTracker;

final class TrackTransactionalStream implements TransactionalStreamTracker
{
    use InteractWithTracker;

    public function newStory(string $eventName): TransactionalStreamStory
    {
        return new TransactionalEventDraft($eventName);
    }
}
