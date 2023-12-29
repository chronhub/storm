<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Workflow\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Workflow\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Workflow\Activity\LoopHandler;
use Chronhub\Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Workflow\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Workflow\Activity\RisePersistentProjection;
use Chronhub\Storm\Projector\Workflow\Activity\RunUntil;

final readonly class PersistentActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): array
    {
        if (! $management instanceof PersistentManagement) {
            throw new RuntimeException('Management must be instance of PersistentManagement');
        }

        $timer = $this->getTimer($subscriptor);
        $eventProcessor = $this->getEventProcessor($subscriptor, $scope);

        return [
            fn (): callable => new LoopHandler(),
            fn (): callable => new RunUntil($timer),
            fn (): callable => new RisePersistentProjection(),
            fn (): callable => $this->getStreamLoader($subscriptor),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap(),
            fn (): callable => new PersistOrUpdate(),
            fn (): callable => new DispatchSignal($subscriptor->option()->getSignal()),
            fn (): callable => new RefreshProjection($subscriptor->option()->getOnlyOnceDiscovery()),
        ];
    }
}
