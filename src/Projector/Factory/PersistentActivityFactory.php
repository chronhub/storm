<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Workflow\Activity\CycleObserver;
use Chronhub\Storm\Projector\Workflow\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Workflow\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Workflow\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Workflow\Activity\RisePersistentProjection;

final readonly class PersistentActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscriptor $subscriptor, ProjectorScope $scope): array
    {
        $eventProcessor = $this->createStreamEventReactor($subscriptor, $scope);

        return [
            fn (): callable => new CycleObserver(),
            fn (): callable => new RisePersistentProjection(),
            fn (): callable => $this->createStreamLoader($subscriptor),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap(),
            fn (): callable => new PersistOrUpdate(),
            fn (): callable => new DispatchSignal($subscriptor->option()->getSignal()),
            fn (): callable => new RefreshProjection($subscriptor->option()->getOnlyOnceDiscovery()),
        ];
    }
}
