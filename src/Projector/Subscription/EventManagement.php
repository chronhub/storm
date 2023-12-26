<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Observer\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Observer\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionSynchronized;

final class EventManagement
{
    public static function subscribe(Notification $notification, PersistentManagement $management): void
    {
        $notification->listen(ProjectionRise::class, fn () => $management->rise());

        $notification->listen(ProjectionLockUpdated::class, fn () => $management->tryUpdateLock());

        $notification->listen(ProjectionStored::class, fn () => $management->store());

        $notification->listen(ProjectionPersistedWhenThresholdIsReached::class, fn () => $management->persistWhenCounterIsReached());

        $notification->listen(ProjectionClosed::class, fn () => $management->close());

        $notification->listen(ProjectionRevised::class, fn () => $management->revise());

        $notification->listen(ProjectionDiscarded::class, fn ($listener) => $management->discard($listener->withEmittedEvents));

        $notification->listen(ProjectionFreed::class, fn () => $management->freed());

        $notification->listen(ProjectionRestarted::class, fn () => $management->restart());

        $notification->listen(ProjectionStatusDisclosed::class, fn () => $management->disclose());

        $notification->listen(ProjectionSynchronized::class, fn () => $management->synchronise());

        if ($management instanceof EmittingManagement) {
            $notification->listen(EventEmitted::class, fn ($listener) => $management->emit($listener->event));

            $notification->listen(EventLinkedTo::class, fn ($listener) => $management->linkTo($listener->streamName, $listener->event));
        }
    }
}
