<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\CycleHasStarted;
use Chronhub\Storm\Projector\Subscription\Notification\CycleStarted;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\IsTimeStarted;
use Chronhub\Storm\Projector\Subscription\Notification\SprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\TimeStarted;

final class CycleObserver
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $this->onCycleStarted($hub);

        $next($hub);

        return $this->onCycleChanged($hub);
    }

    private function onCycleStarted(NotificationHub $hub): void
    {
        if (! $hub->expect(CycleHasStarted::class)) {
            $hub->notify(CycleStarted::class);
        }

        if (! $hub->expect(IsTimeStarted::class)) {
            $hub->notify(TimeStarted::class);
        }
    }

    private function onCycleChanged(NotificationHub $hub): bool
    {
        $shouldStop = $hub->expect(IsSprintTerminated::class);

        if ($shouldStop) {
            $hub->notify(SprintTerminated::class);
        }

        $hub->notify(CycleChanged::class, $shouldStop);

        return ! $shouldStop;
    }
}
