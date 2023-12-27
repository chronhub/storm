<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsRising;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;

final readonly class RiseQueryProjection
{
    public function __invoke(HookHub $task, callable $next): callable|bool
    {
        if ($task->listen(IsRising::class)) {
            $task->listen(StreamsDiscovered::class);
        }

        return $next($task);
    }
}
