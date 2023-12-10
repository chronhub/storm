<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\Stats;
use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class RiseQueryProjection
{
    public function __construct(private Stats $stats)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->stats->hasStarted()) {
            $subscription->discoverStreams();
        }

        $this->stats->inc();

        return $next($subscription);
    }
}
