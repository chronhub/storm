<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\Subscription;

final class PrepareQueryRunner
{
    private bool $isInitialized = false;

    public function __invoke(Subscription $subscription, ?ProjectionManagement $repository, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->isInitialized = true;

            $queries = $subscription->context()->queries();

            $subscription->streamPosition()->watch($queries);
        }

        return $next($subscription, $repository);
    }
}
