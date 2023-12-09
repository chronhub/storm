<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\Stats;

final readonly class RiseQueryProjection
{
    public function __construct(private Stats $stats)
    {
    }

    public function __invoke(QuerySubscriptionInterface $subscription, callable $next): callable|bool
    {
        if (! $this->stats->hasStarted()) {
            $queries = $subscription->context()->queries();

            $subscription->streamManager()->discover($queries);
        }

        $this->stats->inc();

        return $next($subscription);
    }
}
