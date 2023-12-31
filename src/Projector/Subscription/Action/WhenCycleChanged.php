<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CycleReset;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;

final class WhenCycleChanged
{
    public function __invoke(NotificationHub $hub, CycleChanged $event): void
    {
        $this->resetCounter($hub);

        $this->endCycle($hub, $event->sprintTerminated);
    }

    private function resetCounter(NotificationHub $hub): void
    {
        $hub->notifyMany(BatchCounterReset::class, StreamEventAckedReset::class);
    }

    private function endCycle(NotificationHub $hub, bool $sprintTerminated): void
    {
        $hub->notifyWhen($sprintTerminated, CycleReset::class, null,
            fn (NotificationHub $hub) => $hub->notify(CycleIncremented::class)
        );
    }
}
