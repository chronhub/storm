<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Closure;

final class PrepareQueryRunner
{
    private bool $isFirstExecution = true;

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        if ($this->isFirstExecution) {
            $this->isFirstExecution = false;

            $queries = $subscription->context()->queries();

            $subscription->streamPosition()->watch($queries);
        }

        return $next($subscription);
    }
}
