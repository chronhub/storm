<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentProjector;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Closure;

final readonly class StopWhenRunningOnce
{
    public function __construct(private PersistentProjector $projector)
    {
    }

    public function __invoke(PersistentSubscriptionInterface $subscription, Closure $next): Closure|bool
    {
        if (! $subscription->sprint()->inBackground() && $subscription->sprint()->inProgress()) {
            $this->projector->stop();
        }

        return $next($subscription);
    }
}
