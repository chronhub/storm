<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Activity\RisePersistentProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Activity\StopWhenRunningOnce;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;

trait InteractWithPersistentProjection
{
    public function run(bool $inBackground): void
    {
        if (! $this->subscription->context()->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Persistent projection requires a projection query filter');
        }

        $this->subscription->start($inBackground);

        $project = new RunProjection($this->subscription, $this->newWorkflow());

        $project->beginCycle();
    }

    public function stop(): void
    {
        $this->subscription->close();
    }

    public function reset(): void
    {
        $this->subscription->revise();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->subscription->discard($withEmittedEvents);
    }

    public function getState(): array
    {
        return $this->subscription->state()->get();
    }

    public function getName(): string
    {
        return $this->subscription->getName();
    }

    protected function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new RisePersistentProjection(),
            new LoadStreams(),
            new HandleStreamEvent(
                new EventProcessor(
                    $this->subscription->context()->reactors(),
                    $this->getScope()
                )),
            new HandleStreamGap(),
            new PersistOrUpdate(),
            new ResetEventCounter(),
            new DispatchSignal(),
            new RefreshProjection(),
            new StopWhenRunningOnce(),
        ];

        return new Workflow($this->subscription, $activities);
    }

    abstract protected function getScope(): ProjectorScope;
}
