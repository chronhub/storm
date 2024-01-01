<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Workflow\Activity\CycleObserver;
use Chronhub\Storm\Projector\Workflow\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Workflow\Activity\SleepForQuery;

final readonly class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscriptor $subscriptor, ProjectorScope $scope): array
    {
        $eventProcessor = $this->getEventProcessor($subscriptor, $scope);

        return [
            fn (): callable => new CycleObserver(),
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => $this->getStreamLoader($subscriptor),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new SleepForQuery(),
            fn (): callable => new DispatchSignal($subscriptor->option()->getSignal()),
        ];
    }
}
