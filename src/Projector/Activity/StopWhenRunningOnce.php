<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Contracts\Projector\PersistentProjector;

final readonly class StopWhenRunningOnce
{
    public function __construct(private PersistentProjector $projector)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $subscription->sprint()->inBackground() && $subscription->sprint()->inProgress()) {
            $this->projector->stop();
        }

        return $next($subscription);
    }
}
