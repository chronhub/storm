<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Batch\BatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleReset;
use Chronhub\Storm\Projector\Subscription\MasterCounter\MasterCounterReset;
use Chronhub\Storm\Projector\Subscription\Sprint\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Sprint\SprintTerminated;
use Chronhub\Storm\Projector\Subscription\Stream\NewEventStreamReset;
use Chronhub\Storm\Projector\Subscription\Stream\StreamEventAckedReset;
use Chronhub\Storm\Projector\Subscription\Timer\TimeReset;

final class WhenCycleChanged
{
    public function __invoke(NotificationHub $hub, CycleChanged $event): void
    {
        $this->notifySprintTerminated($hub);

        $this->notifyCycledEnded($hub);

        $this->resetCycle($hub);

        $this->forgetListenersOnTermination($hub);
    }

    private function notifySprintTerminated(NotificationHub $hub): void
    {
        $hub->notifyWhen(
            $hub->expect(IsSprintTerminated::class),
            fn (NotificationHub $hub) => $hub->notify(SprintTerminated::class)
        );
    }

    private function resetCycle(NotificationHub $hub): void
    {
        $hub->notifyMany(BatchCounterReset::class, StreamEventAckedReset::class, NewEventStreamReset::class);

        if ($hub->expect(IsSprintTerminated::class)) {
            $hub->notifyMany(TimeReset::class, MasterCounterReset::class);
        }
    }

    private function notifyCycledEnded(NotificationHub $hub): void
    {
        $hub->notifyWhen(
            $hub->expect(IsSprintTerminated::class),
            fn (NotificationHub $hub) => $hub->notify(CycleReset::class),
            fn (NotificationHub $hub) => $hub->notify(CycleIncremented::class)
        );
    }

    private function forgetListenersOnTermination(NotificationHub $hub): void
    {
        if ($hub->expect(IsSprintTerminated::class)) {
            $hub->forgetAll();
        }
    }
}
