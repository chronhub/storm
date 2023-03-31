<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;

final readonly class HandleGap
{
    public function __construct(private SubscriptionManagement $repository)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if ($subscription->gap->hasGap()) {
            $subscription->gap->sleep();

            $this->repository->store();
        }

        return $next($subscription);
    }
}
