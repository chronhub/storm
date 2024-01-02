<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleBegan;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleRenewed;
use Chronhub\Storm\Projector\Subscription\Sprint\IsSprintTerminated;

final class CycleObserver
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $hub->notify(CycleBegan::class);

        $next($hub);

        return $this->onCycleChanged($hub);
    }

    private function onCycleChanged(NotificationHub $hub): bool
    {
        $hub->notify(CycleRenewed::class);

        return ! $hub->expect(IsSprintTerminated::class);
    }
}
