<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\GetNewEventStreams;
use Chronhub\Storm\Projector\Subscription\Notification\HasEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\NewEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\NoEventStreamDiscovered;

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
