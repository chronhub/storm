<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Observer\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Observer\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Observer\PersistWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;

final class EventManagement
{
    public static function subscribe(Notification $notification, PersistentManagement $management): void
    {
        $notification->listen(ProjectionRise::class, fn () => $management->rise());

        $notification->listen(ProjectionLockUpdated::class, fn () => $management->tryUpdateLock());

        $notification->listen(ProjectionStored::class, fn () => $management->store());

        $notification->listen(PersistWhenThresholdIsReached::class, fn () => $management->persistWhenCounterIsReached());

        $notification->listen(ProjectionClosed::class, fn () => $management->close());

        $notification->listen(ProjectionRevised::class, fn () => $management->revise());

        $notification->listen(ProjectionDiscarded::class, fn ($listener) => $management->discard($listener->withEmittedEvents));

        if ($management instanceof EmittingManagement) {
            $notification->listen(EventEmitted::class, fn ($listener) => $management->emit($listener->event));

            $notification->listen(EventLinkedTo::class, fn ($listener) => $management->linkTo($listener->streamName, $listener->event));
        }
    }
}
