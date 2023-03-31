<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;

final class PrepareQueryRunner
{
    private bool $isInitialized = false;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->isInitialized = true;

            $subscription->streamPosition->watch(
                $subscription->context()->queries()
            );
        }

        return $next($subscription);
    }
}
