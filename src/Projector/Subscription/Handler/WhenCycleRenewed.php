<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Stream\ShouldSnapshotCheckpoint;
use Chronhub\Storm\Projector\Subscription\Batch\BatchReset;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleRenewed;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleReset;
use Chronhub\Storm\Projector\Subscription\MasterCounter\MasterCounterReset;
use Chronhub\Storm\Projector\Subscription\Sprint\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Sprint\SprintTerminated;
use Chronhub\Storm\Projector\Subscription\Stream\NewEventStreamReset;
use Chronhub\Storm\Projector\Subscription\Stream\StreamEventAckedReset;
use Chronhub\Storm\Projector\Subscription\Timer\TimeReset;

final class WhenCycleRenewed
{
    public function __invoke(NotificationHub $hub, CycleRenewed $event): void
    {
        $this->notifySprintTerminated($hub);

        $this->notifyCycledEnded($hub);

        $this->flushWatcher($hub);
    }

    private function notifySprintTerminated(NotificationHub $hub): void
    {
        $hub->notifyWhen(
            $hub->expect(IsSprintTerminated::class),
            fn (NotificationHub $hub) => $hub->notify(SprintTerminated::class)
        );
    }

    private function flushWatcher(NotificationHub $hub): void
    {
        // reset every cycle
        $hub->notifyMany(BatchReset::class, StreamEventAckedReset::class, NewEventStreamReset::class);

        // reset only when sprint is terminated
        if ($hub->expect(IsSprintTerminated::class)) {
            $hub->notifyMany(TimeReset::class, MasterCounterReset::class);
        }
    }

    private function notifyCycledEnded(NotificationHub $hub): void
    {
        $isSprintTerminated = $hub->expect(IsSprintTerminated::class);

        $hub->notifyWhen(
            $isSprintTerminated,
            fn (NotificationHub $hub) => $hub->notify(CycleReset::class),
            fn (NotificationHub $hub) => $hub->notify(CycleIncremented::class),
        );

        // required when rerun projection
        if ($isSprintTerminated) {
            $hub->forgetListener(ShouldSnapshotCheckpoint::class);
            $hub->forgetListener(CheckpointInserted::class);
        }
    }
}
