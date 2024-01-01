<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CycleReset;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\MasterCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\SprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;
use Chronhub\Storm\Projector\Subscription\Notification\TimeReset;

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
        $hub->notifyMany(BatchCounterReset::class, StreamEventAckedReset::class);

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
