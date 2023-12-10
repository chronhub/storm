<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class HandleStreamGap
{
    public function __construct(private SubscriptionManagement $subscription)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        // When a gap is detected and still retry left,
        // we sleep and store the projection if some event(s) has been handled
        if ($subscription->hasGapDetection() && $subscription->streamManager->hasGap()) {
            $subscription->streamManager->sleep();

            if (! $subscription->eventCounter->isReset()) {
                $this->subscription->store();
            }
        }

        return $next($subscription);
    }
}
