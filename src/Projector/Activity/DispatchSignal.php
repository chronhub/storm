<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;

use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Subscriber $subscriber, callable $next): callable|bool
    {
        if ($subscriber->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($subscriber);
    }
}
