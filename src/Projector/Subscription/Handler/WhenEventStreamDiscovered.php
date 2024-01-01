<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\GetNewEventStreams;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\HasEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\NewEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\NoEventStreamDiscovered;

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
