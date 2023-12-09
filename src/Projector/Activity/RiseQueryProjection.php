<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Projector\Scheme\Stats;

final readonly class RiseQueryProjection
{
    public function __construct(private Stats $stats)
    {
    }

    public function __invoke(QuerySubscriber $subscriber, callable $next): callable|bool
    {
        if (! $this->stats->hasStarted()) {
            $queries = $subscriber->context()->queries();

            $subscriber->streamBinder->discover($queries);
        }

        $this->stats->inc();

        return $next($subscriber);
    }
}
