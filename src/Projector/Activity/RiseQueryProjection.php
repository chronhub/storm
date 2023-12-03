<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;

final class RiseQueryProjection
{
    private bool $isFirstExecution = true;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($this->isFirstExecution) {
            $this->isFirstExecution = false;

            $queries = $subscription->context()->queries();

            $subscription->streamManager()->watchStreams($queries);
        }

        return $next($subscription);
    }
}
