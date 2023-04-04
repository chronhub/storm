<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;

final readonly class HandleStreamGap
{
    public function __construct(private ProjectionManagement $repository)
    {
    }

    public function __invoke(PersistentSubscriptionInterface $subscription, callable $next): callable|bool
    {
        if ($subscription->gap()->hasGap()) {
            $subscription->gap()->sleep();

            $this->repository->store();
        }

        return $next($subscription);
    }
}
