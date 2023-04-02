<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;

final readonly class HandleGap
{
    public function __construct(private ProjectionRepositoryInterface $repository)
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
