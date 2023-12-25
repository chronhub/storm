<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if ($subscriptor->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($subscriptor);
    }
}
