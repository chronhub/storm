<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;

final class PersistentActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscription $subscription, ProjectorScope $scope, ?PersistentManagement $management): array
    {
        $timer = $this->getTimer($subscription);
        $noEventCounter = $this->getNoStreamLoadedCounter($subscription);
        $eventProcessor = $this->getEventProcessor($subscription, $scope, $management);
        $queryFilterResolver = $this->getQueryFilterResolver($subscription);
        $monitor = $this->getMonitor();

        return [
            fn (): callable => new RunUntil($timer),
            fn (): callable => new RisePersistentProjection($monitor, $management),
            fn (): callable => new LoadStreams($noEventCounter, $queryFilterResolver),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap($management),
            fn (): callable => new PersistOrUpdate($management, $noEventCounter),
            fn (): callable => new ResetEventCounter(),
            fn (): callable => new DispatchSignal(),
            fn (): callable => new RefreshProjection($monitor, $management),
        ];
    }

    protected function getMonitor(): MonitorRemoteStatus
    {
        return new MonitorRemoteStatus();
    }
}
