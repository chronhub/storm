<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;

final class PrepareQueryRunner
{
    private bool $isFirstExecution = true;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($this->isFirstExecution) {
            $this->isFirstExecution = false;

            $queries = $subscription->context()->queries();

            $subscription->streamPosition()->watch($queries);
        }

        return $next($subscription);
    }
}
