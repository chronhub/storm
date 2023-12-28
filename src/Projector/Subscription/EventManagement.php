<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Engagement\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Engagement\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionSynchronized;

final class EventManagement
{
    public static function subscribe(HookHub $task, PersistentManagement $management): void
    {
        $task->addHook(ProjectionRise::class, fn () => $management->rise());

        $task->addHook(ProjectionLockUpdated::class, fn () => $management->tryUpdateLock());

        $task->addHook(ProjectionStored::class, fn () => $management->store());

        $task->addHook(ProjectionPersistedWhenThresholdIsReached::class, fn () => $management->persistWhenThresholdIsReached());

        $task->addHook(ProjectionClosed::class, fn () => $management->close());

        $task->addHook(ProjectionRevised::class, fn () => $management->revise());

        $task->addHook(ProjectionDiscarded::class, fn ($listener) => $management->discard($listener->withEmittedEvents));

        $task->addHook(ProjectionFreed::class, fn () => $management->freed());

        $task->addHook(ProjectionRestarted::class, fn () => $management->restart());

        $task->addHook(ProjectionStatusDisclosed::class, fn () => $management->disclose());

        $task->addHook(ProjectionSynchronized::class, fn () => $management->synchronise());

        if ($management instanceof EmittingManagement) {
            $task->addHook(EventEmitted::class, fn ($listener) => $management->emit($listener->event));

            $task->addHook(EventLinkedTo::class, fn ($listener) => $management->linkTo($listener->streamName, $listener->event));
        }
    }
}
