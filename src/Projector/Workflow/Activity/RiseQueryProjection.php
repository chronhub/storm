<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\IsFirstLoop;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;

final readonly class RiseQueryProjection
{
    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if ($hub->expect(IsFirstLoop::class)) {
            $hub->notify(StreamsDiscovered::class);
        }

        return $next($hub);
    }
}
