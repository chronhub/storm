<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Workflow\Activity\LoadStreams;
use Chronhub\Storm\Projector\Workflow\EventReactor;
use Chronhub\Storm\Projector\Workflow\QueryFilterResolver;
use Chronhub\Storm\Projector\Workflow\Timer;

use function array_map;

abstract readonly class AbstractActivityFactory implements ActivityFactory
{
    public function __construct(protected Chronicler $chronicler)
    {
    }

    public function __invoke(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($subscriptor, $scope, $management)
        );
    }

    protected function getQueryFilterResolver(Subscriptor $subscriptor): QueryFilterResolver
    {
        return new QueryFilterResolver($subscriptor->getContext()->queryFilter());
    }

    protected function getEventProcessor(Subscriptor $subscriptor, ProjectorScope $scope): EventReactor
    {
        return new EventReactor(
            $subscriptor->getContext()->reactors(),
            $scope,
            $subscriptor->option()->getSignal()
        );
    }

    protected function getTimer(Subscriptor $subscriptor): Timer
    {
        return new Timer($subscriptor->clock(), $subscriptor->getContext()->timer());
    }

    protected function getStreamLoader(Subscriptor $subscriptor): LoadStreams
    {
        return new LoadStreams(
            $this->chronicler,
            $subscriptor->clock(),
            $subscriptor->option()->getLoadLimiter(),
            $this->getQueryFilterResolver($subscriptor) // todo should be configurable in integration
        );
    }

    /**
     * @return array<callable>
     */
    abstract protected function activities(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): array;
}
