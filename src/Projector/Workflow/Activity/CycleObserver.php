<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\Cycle\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\Cycle\CycleStarted;
use Chronhub\Storm\Projector\Subscription\Notification\Cycle\IsCycleStarted;
use Chronhub\Storm\Projector\Subscription\Notification\Sprint\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\Timer\IsTimeStarted;
use Chronhub\Storm\Projector\Subscription\Notification\Timer\TimeStarted;

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
