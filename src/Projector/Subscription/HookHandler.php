<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Hook\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Hook\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionSynchronized;

final class HookHandler
{
    public static function subscribe(NotificationHub $task, PersistentManagement $management): void
    {
        $task->addHooks([
            ProjectionRise::class => fn () => $management->rise(),
            ProjectionLockUpdated::class => fn () => $management->tryUpdateLock(),
            ProjectionStored::class => fn () => $management->store(),
            ProjectionPersistedWhenThresholdIsReached::class => fn () => $management->persistWhenThresholdIsReached(),
            ProjectionClosed::class => fn () => $management->close(),
            ProjectionRevised::class => fn () => $management->revise(),
            ProjectionDiscarded::class => fn ($listener) => $management->discard($listener->withEmittedEvents),
            ProjectionFreed::class => fn () => $management->freed(),
            ProjectionRestarted::class => fn () => $management->restart(),
            ProjectionStatusDisclosed::class => fn () => $management->disclose(),
            ProjectionSynchronized::class => fn () => $management->synchronise(),
        ]);

        if ($management instanceof EmittingManagement) {
            $task->addHooks([
                EventEmitted::class => fn ($listener) => $management->emit($listener->event),
                EventLinkedTo::class => fn ($listener) => $management->linkTo($listener->streamName, $listener->event),
            ]);
        }
    }
}
