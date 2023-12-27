<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;

final readonly class ResetEventCounter
{
    public function __invoke(HookHub $task, callable $next): callable|bool
    {
        $task->interact(EventReset::class);

        return $next($task);
    }
}
