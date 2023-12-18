<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;

use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($subscription->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($subscription);
    }
}
