<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;

final class TrackTransactionalStream extends TrackStream implements TransactionalStreamTracker
{
    public function newStory(string $eventName): TransactionalStreamStory
    {
        return new TransactionalEventDraft($eventName);
    }
}
