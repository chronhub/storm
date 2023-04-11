<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\Subscription;
use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Subscription $subscription, ?ProjectionManagement $repository, callable $next): callable|bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($subscription, $repository);
    }
}
