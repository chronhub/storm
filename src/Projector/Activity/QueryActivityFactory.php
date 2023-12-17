<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;

final class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscription $subscription, ProjectorScope $scope, ?PersistentManagement $management): array
    {
        $timer = $this->getTimer($subscription);
        $eventProcessor = $this->getEventProcessor($subscription, $scope, $management);
        $queryFilterResolver = $this->getQueryFilterResolver($subscription);
        $noEventCounter = $this->getNoStreamLoadedCounter($subscription);

        return [
            fn (): callable => new RunUntil($timer),
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => new LoadStreams($noEventCounter, $queryFilterResolver),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new SleepForQuery($noEventCounter),
            fn (): callable => new DispatchSignal(),
        ];
    }
}
