<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Notification\LoopHasStarted;
use Chronhub\Storm\Projector\Subscription\Notification\LoopIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\LoopRenew;
use Chronhub\Storm\Projector\Subscription\Notification\LoopReset;
use Chronhub\Storm\Projector\Subscription\Notification\LoopStarted;

final class LoopHandler
{
    public function __invoke(NotificationHub $hub, callable $next): bool
    {
        $this->shouldStartLoop($hub);

        $response = $next($hub);

        $this->shouldEndLoop($hub);

        return $response;
    }

    private function shouldStartLoop(NotificationHub $hub): void
    {
        if (! $hub->expect(LoopHasStarted::class)) {
            $hub->notify(LoopStarted::class);
        }
    }

    private function shouldEndLoop(NotificationHub $hub): void
    {
        $shouldStop = $this->shouldStop($hub);

        $loopNotification = $shouldStop ? LoopReset::class : LoopIncremented::class;

        $hub->notify($loopNotification);
        $hub->notify(LoopRenew::class);
    }

    private function shouldStop(NotificationHub $hub): bool
    {
        return $hub->expect(ExpectSprintTermination::class);
    }
}
