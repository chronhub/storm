<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Management\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Management\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionSynchronized;

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
