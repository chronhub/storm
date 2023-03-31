<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;

final class UpdateStatusAndPositions
{
    use RemoteStatusDiscovery;

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $this->refresh(false, $subscription->runner->inBackground());

        $subscription->streamPosition->watch(
            $subscription->context()->queries()
        );

        return $next($subscription);
    }
}
