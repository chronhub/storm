<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class RiseQueryProjection
{
    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($subscription->looper->isFirstLoop()) {
            $subscription->discoverStreams();
        }

        return $next($subscription);
    }
}
