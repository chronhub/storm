<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\ResetAckedEvent;

final class FinalizeProjection
{
    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $subscriptor->notify(new ResetAckedEvent());

        return $next($subscriptor);
    }
}
