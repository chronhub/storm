<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;

final readonly class HandleStreamGap
{
    public function __invoke(
        PersistentSubscriptionInterface $subscription,
        ProjectionManagement $repository,
        callable $next,
    ): callable|bool {
        if ($subscription->gap()->hasGap()) {
            $subscription->gap()->sleep();

            $repository->store();
        }

        return $next($subscription, $repository);
    }
}
