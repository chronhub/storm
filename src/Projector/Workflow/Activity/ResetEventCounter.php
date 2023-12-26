<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;

final readonly class ResetEventCounter
{
    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $subscriptor->receive(new EventReset());

        return $next($subscriptor);
    }
}
