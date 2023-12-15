<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;

final class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscription $subscription, ?PersistentManagement $management, ProjectorScope $scope): array
    {
        $eventProcessor = $this->getEventProcessor($subscription, $management, $scope);
        $queryFilterResolver = $this->getQueryFilterResolver($subscription);

        return [
            fn (): callable => new RunUntil($subscription->clock, $subscription->context()->timer()),
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => new LoadStreams($queryFilterResolver, null),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new DispatchSignal(),
        ];
    }
}
