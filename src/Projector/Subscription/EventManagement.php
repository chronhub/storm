<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
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
    public static function subscribe(HookHub $task, PersistentManagement $management): void
    {
        $task->addHook(ProjectionRise::class, fn () => $management->rise());

        $task->addHook(ProjectionLockUpdated::class, fn () => $management->tryUpdateLock());

        $task->addHook(ProjectionStored::class, fn () => $management->store());

        $task->addHook(ProjectionPersistedWhenThresholdIsReached::class, fn () => $management->persistWhenCounterIsReached());

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
