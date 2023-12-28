<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchReset;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAckedReset;

final class FinalizeProjection
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        // todo by now we let this here,

        $hub->interact(BatchReset::class);

        $hub->interact(StreamEventAckedReset::class);

        return $next($hub);
    }
}
