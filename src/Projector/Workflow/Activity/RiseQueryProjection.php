<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;

final readonly class RiseQueryProjection
{
    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if ($subscriptor->isRising()) {
            $subscriptor->receive(new StreamsDiscovered());
        }

        return $next($subscriptor);
    }
}
