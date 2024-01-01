<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\CycleStarted;
use Chronhub\Storm\Projector\Subscription\Notification\IsCycleStarted;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\IsTimeStarted;
use Chronhub\Storm\Projector\Subscription\Notification\TimeStarted;

final class CycleObserver
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $this->onCycleStarted($hub);

        $next($hub);

        return $this->onCycleChanged($hub);
    }

    public function onCycleStarted(NotificationHub $hub): void
    {
        $hub->notifyWhen(
            ! $hub->expect(IsCycleStarted::class),
            fn () => $hub->notify(CycleStarted::class)
        )->notifyWhen(
            ! $hub->expect(IsTimeStarted::class),
            fn () => $hub->notify(TimeStarted::class)
        );
    }

    private function onCycleChanged(NotificationHub $hub): bool
    {
        $hub->notify(CycleChanged::class);

        return ! $hub->expect(IsSprintTerminated::class);
    }
}
