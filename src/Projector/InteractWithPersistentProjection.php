<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Activity\PreparePersistentRunner;
use Chronhub\Storm\Projector\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Activity\StopWhenRunningOnce;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;

trait InteractWithPersistentProjection
{
    public function run(bool $inBackground): void
    {
        $this->subscription->compose($this->context, $this->getScope(), $inBackground);

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

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    protected function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new PreparePersistentRunner(),
            new HandleStreamEvent(new LoadStreams($this->subscription->chronicler())),
            new HandleStreamGap(),
            new PersistOrUpdate(),
            new ResetEventCounter(),
            new DispatchSignal(),
            new RefreshProjection(),
            new StopWhenRunningOnce($this),
        ];

        return new Workflow($this->subscription, $activities);
    }

    abstract protected function getScope(): ProjectorScope;
}
