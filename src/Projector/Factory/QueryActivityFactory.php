<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Workflow\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Workflow\Activity\LoadStreams;
use Chronhub\Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Workflow\Activity\RunUntil;
use Chronhub\Storm\Projector\Workflow\Activity\SleepForQuery;

final class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscription $subscription, ProjectorScope $scope, Management $management): array
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
