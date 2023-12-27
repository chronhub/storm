<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\AckedEventReset;

final class FinalizeProjection
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        $hub->listen(AckedEventReset::class);

        return $next($hub);
    }
}
