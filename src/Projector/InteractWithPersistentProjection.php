<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PersistOrUpdateLock;
use Chronhub\Storm\Projector\Activity\PreparePersistentRunner;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Activity\StopWhenRunningOnce;
use Chronhub\Storm\Projector\Activity\UpdateStatusAndPositions;

trait InteractWithPersistentProjection
{
    public function run(bool $inBackground): void
    {
        $this->subscription->compose($this->context, $this->getCaster(), $inBackground);

        $project = new RunProjection($this->activities());

        $project($this->subscription, $this->repository);
    }

    public function stop(): void
    {
        $this->repository->close();
    }

    public function reset(): void
    {
        $this->repository->revise();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->repository->discard($withEmittedEvents);
    }

    public function getState(): array
    {
        return $this->subscription->state()->get();
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    /**
     * @return array<callable>
     */
    protected function activities(): array
    {
        return [
            new PreparePersistentRunner(),
            new HandleStreamEvent(new LoadStreams($this->chronicler)),
            new HandleStreamGap(),
            new PersistOrUpdateLock(),
            new ResetEventCounter(),
            new DispatchSignal(),
            new UpdateStatusAndPositions(),
            new StopWhenRunningOnce($this),
        ];
    }

    abstract protected function getCaster(): Caster;
}
