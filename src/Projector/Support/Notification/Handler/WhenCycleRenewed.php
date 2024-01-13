<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Checkpoint\ShouldSnapshotCheckpoint;
use Chronhub\Storm\Projector\Support\Notification\Batch\BatchReset;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleIncremented;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleRenewed;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleReset;
use Chronhub\Storm\Projector\Support\Notification\MasterCounter\MasterCounterReset;
use Chronhub\Storm\Projector\Support\Notification\Sprint\IsSprintTerminated;
use Chronhub\Storm\Projector\Support\Notification\Sprint\SprintTerminated;
use Chronhub\Storm\Projector\Support\Notification\Stream\NewEventStreamReset;
use Chronhub\Storm\Projector\Support\Notification\Stream\StreamEventAckedReset;
use Chronhub\Storm\Projector\Support\Notification\Timer\TimeReset;

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
