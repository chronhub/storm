<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleBegan;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleStarted;
use Chronhub\Storm\Projector\Subscription\Cycle\IsCycleStarted;
use Chronhub\Storm\Projector\Subscription\Timer\IsTimeStarted;
use Chronhub\Storm\Projector\Subscription\Timer\TimeStarted;

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
