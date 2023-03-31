<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\PersistentSubscription;

final readonly class HandleGap
{
    public function __construct(private ProjectionRepository $repository)
    {
    }

    public function __invoke(PersistentSubscription $subscription, callable $next): callable|bool
    {
        if ($subscription->gap()->hasGap()) {
            $subscription->gap()->sleep();

            $this->repository->store();
        }

        return $next($subscription);
    }
}
