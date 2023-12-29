<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventCounterReset;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Notification\LoopHasStarted;
use Chronhub\Storm\Projector\Subscription\Notification\LoopIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\LoopReset;
use Chronhub\Storm\Projector\Subscription\Notification\LoopStarted;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;

final class LoopHandler
{
    public function __invoke(HookHub $hub, callable $next): bool
    {
        $this->shouldStartLoop($hub);

        $response = $next($hub);

        $this->shouldEndLoop($hub);

        $this->finalizeCycle($hub);

        return $response;
    }

    private function shouldStartLoop(HookHub $hub): void
    {
        if (! $hub->expect(LoopHasStarted::class)) {
            $hub->notify(LoopStarted::class);
        }
    }

    private function shouldEndLoop(HookHub $hub): void
    {
        $loopNotification = $this->shouldStop($hub) ? LoopReset::class : LoopIncremented::class;

        $hub->notify($loopNotification);
    }

    private function shouldStop(HookHub $hub): bool
    {
        return $hub->expect(ExpectSprintTermination::class);
    }

    private function finalizeCycle(HookHub $hub): void
    {
        $hub->notify(EventCounterReset::class);

        $hub->notify(StreamEventAckedReset::class);
    }
}
