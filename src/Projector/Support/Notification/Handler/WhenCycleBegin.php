<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleBegan;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleStarted;
use Chronhub\Storm\Projector\Support\Notification\Cycle\IsCycleStarted;
use Chronhub\Storm\Projector\Support\Notification\Timer\IsTimeStarted;
use Chronhub\Storm\Projector\Support\Notification\Timer\TimeStarted;

final class WhenCycleBegin
{
    public function __invoke(NotificationHub $hub, CycleBegan $event): void
    {
        $hub->notifyWhen(
            ! $hub->expect(IsCycleStarted::class),
            fn () => $hub->notify(CycleStarted::class)
        )->notifyWhen(
            ! $hub->expect(IsTimeStarted::class),
            fn () => $hub->notify(TimeStarted::class)
        );
    }
}
