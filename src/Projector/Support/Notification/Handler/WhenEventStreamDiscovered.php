<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;
use Chronhub\Storm\Projector\Support\Notification\Stream\GetNewEventStreams;
use Chronhub\Storm\Projector\Support\Notification\Stream\HasEventStreamDiscovered;
use Chronhub\Storm\Projector\Support\Notification\Stream\NewEventStreamDiscovered;
use Chronhub\Storm\Projector\Support\Notification\Stream\NoEventStreamDiscovered;

final class WhenEventStreamDiscovered
{
    public function __invoke(NotificationHub $hub, EventStreamDiscovered $capture): void
    {
        if (! $hub->expect(HasEventStreamDiscovered::class)) {
            $hub->notify(NoEventStreamDiscovered::class);
        } else {
            $newEventStreams = $hub->expect(GetNewEventStreams::class);

            foreach ($newEventStreams as $newEventStream) {
                $hub->notify(NewEventStreamDiscovered::class, $newEventStream);
            }
        }
    }
}
