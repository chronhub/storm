<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Beacon;

use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        if ($manager->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($manager);
    }
}
