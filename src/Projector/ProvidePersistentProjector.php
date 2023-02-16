<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Pipes\DispatchSignal;
use Chronhub\Storm\Projector\Pipes\HandleStreamEvent;
use Chronhub\Storm\Projector\Pipes\ResetEventCounter;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;
use Chronhub\Storm\Projector\Pipes\PersistOrUpdateLock;
use Chronhub\Storm\Projector\Pipes\StopWhenRunningOnce;
use Chronhub\Storm\Projector\Pipes\PreparePersistentRunner;
use Chronhub\Storm\Projector\Pipes\UpdateStatusAndPositions;

trait ProvidePersistentProjector
{
    public function run(bool $inBackground): void
    {
        $this->context->compose($this->getCaster(), $inBackground);

        $project = new RunProjection($this->pipes(), $this->repository);

        $project($this->context);
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
        return $this->context->state->get();
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    /**
     * @return array<callable>
     */
    protected function pipes(): array
    {
        return [
            new PreparePersistentRunner($this->repository),
            new HandleStreamEvent($this->chronicler, $this->repository),
            new PersistOrUpdateLock($this->repository),
            new ResetEventCounter(),
            new DispatchSignal(),
            new UpdateStatusAndPositions($this->repository),
            new StopWhenRunningOnce($this),
        ];
    }

    abstract protected function getCaster(): ProjectorCaster;
}
