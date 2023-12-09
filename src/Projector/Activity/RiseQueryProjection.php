<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\Stats;
use Chronhub\Storm\Projector\Subscription\Beacon;

final readonly class RiseQueryProjection
{
    public function __construct(private Stats $stats)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        if (! $this->stats->hasStarted()) {
            $queries = $manager->context()->queries();

            $manager->streamBinder->discover($queries);
        }

        $this->stats->inc();

        return $next($manager);
    }
}
