<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchReset;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintDaemonize;
use Chronhub\Storm\Projector\Subscription\Notification\LoopHasStarted;
use Chronhub\Storm\Projector\Subscription\Notification\LoopIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\LoopReset;
use Chronhub\Storm\Projector\Subscription\Notification\LoopStarted;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;

final class StartLoop
{
    public function __invoke(HookHub $hub, callable $next): bool
    {
        $this->shouldStartLoop($hub);

        $response = $next($hub);

        $this->shouldEndLoop($hub, $response);

        $this->finalize($hub);

        return $response;
    }

    private function shouldStartLoop(HookHub $hub): void
    {
        if (! $hub->interact(LoopHasStarted::class)) {
            $hub->interact(LoopStarted::class);
        }
    }

    private function shouldEndLoop(HookHub $hub, bool $inProgress): void
    {
        $eventLoop = $this->shouldStop($hub, $inProgress) ? LoopReset::class : LoopIncremented::class;

        $hub->interact($eventLoop);
    }

    private function shouldStop(HookHub $hub, bool $inProgress): bool
    {
        $keepRunning = $hub->interact(IsSprintDaemonize::class);

        return ! $keepRunning || ! $inProgress;
    }

    private function finalize(HookHub $hub): void
    {
        $hub->interact(BatchReset::class);

        $hub->interact(StreamEventAckedReset::class);
    }
}
