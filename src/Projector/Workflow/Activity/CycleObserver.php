<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\CycleHasStarted;
use Chronhub\Storm\Projector\Subscription\Notification\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CycleRenew;
use Chronhub\Storm\Projector\Subscription\Notification\CycleReset;
use Chronhub\Storm\Projector\Subscription\Notification\CycleStarted;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\IsTimeStarted;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;
use Chronhub\Storm\Projector\Subscription\Notification\TimeStarted;

final class CycleObserver
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $this->shouldStart($hub);

        // firing loopRenew event can trigger a callback which updates the sprint
        // so, we cannot trust the response and reevaluate the current progress
        $next($hub);

        return $this->onEnd($hub);
    }

    private function shouldStart(NotificationHub $hub): void
    {
        if (! $hub->expect(CycleHasStarted::class)) {
            $hub->notify(CycleStarted::class);
        }

        if (! $hub->expect(IsTimeStarted::class)) {
            $hub->notify(TimeStarted::class);
        }
    }

    private function onEnd(NotificationHub $hub): bool
    {
        $this->resetCounter($hub);

        $hub->notify(CycleRenew::class); // stopWhen

        $shouldStop = $this->shouldStop($hub);

        $loopEvent = $shouldStop ? CycleReset::class : CycleIncremented::class;

        $hub->notify($loopEvent);

        return ! $shouldStop;
    }

    private function shouldStop(NotificationHub $hub): bool
    {
        return $hub->expect(IsSprintTerminated::class);
    }

    private function resetCounter(NotificationHub $hub): void
    {
        $hub->notify(BatchCounterReset::class);

        $hub->notify(StreamEventAckedReset::class);
    }
}
