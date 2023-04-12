<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Closure;
use function count;
use function is_array;

abstract class AbstractPersistentSubscription
{
    public readonly bool $isPersistent;

    public ?string $currentStreamName = null;

    protected ProjectionStatus $status = ProjectionStatus::IDLE;

    protected ContextInterface $context;

    protected readonly ProjectionStateInterface $state;

    protected readonly Sprint $sprint;

    public function __construct(
        protected readonly ProjectionRepositoryInterface $repository,
        protected readonly ProjectionOption $option,
        protected readonly StreamPosition $streamPosition,
        protected readonly EventCounter $eventCounter,
        protected readonly StreamGapDetector $gap,
        protected readonly SystemClock $clock,
    ) {
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
        $this->isPersistent = true;
    }

    public function compose(ContextInterface $context, Caster $projectorCaster, bool $keepRunning): void
    {
        $this->context = $context;

        if ($this->isPersistent && ! $this->context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Persistent subscription require a projection query filter');
        }

        $this->sprint->runInBackground($keepRunning);

        $this->sprint->continue();

        $this->cast($projectorCaster);
    }

    public function initializeAgain(): void
    {
        $this->state->reset();

        $callback = $this->context->initCallback();

        if ($callback instanceof Closure) {
            $state = $callback();

            if (is_array($state)) {
                $this->state->put($state);
            }
        }
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->discoverStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->streamPosition->all(), $this->state->get());
    }

    public function revise(): void
    {
        $this->resetProjectionState();

        $this->repository->reset(
            $this->streamPosition->all(),
            $this->state->get(),
            $this->status
        );
    }

    public function close(): void
    {
        $this->repository->stop($this->streamPosition->all(), $this->state->get());

        $this->status = ProjectionStatus::IDLE;

        $this->sprint()->stop();
    }

    public function restart(): void
    {
        $this->sprint->continue();

        $this->repository->startAgain();

        $this->status = ProjectionStatus::RUNNING;
    }

    public function boundState(): void
    {
        [$streamPositions, $state] = $this->repository->loadState();

        $this->streamPosition->discover($streamPositions);

        if (is_array($state) && count($state) > 0) {
            $this->state->put($state);
        }
    }

    public function renew(): void
    {
        $this->repository->updateLock($this->streamPosition->all());
    }

    public function freed(): void
    {
        $this->repository->releaseLock();

        $this->status = ProjectionStatus::IDLE;
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function context(): ContextReader
    {
        return $this->context;
    }

    public function sprint(): Sprint
    {
        return $this->sprint;
    }

    public function option(): ProjectionOption
    {
        return $this->option;
    }

    public function streamPosition(): StreamPosition
    {
        return $this->streamPosition;
    }

    public function state(): ProjectionStateInterface
    {
        return $this->state;
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    public function projectionName(): string
    {
        return $this->repository->projectionName();
    }

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    public function gap(): StreamGapDetector
    {
        return $this->gap;
    }

    protected function cast(Caster $caster): void
    {
        $originalState = $this->context->castInitCallback($caster);

        $this->state->put($originalState);

        $this->context->castEventHandlers($caster);
    }

    protected function mountProjection(): void
    {
        $this->sprint()->continue();

        if (! $this->repository->exists()) {
            $this->repository->create($this->status);
        }

        $this->repository->acquireLock();

        $this->status = ProjectionStatus::RUNNING;
    }

    protected function discoverStreams(): void
    {
        $this->streamPosition()->watch($this->context()->queries());

        $this->boundState();
    }

    protected function resetProjectionState(): void
    {
        $this->streamPosition()->reset();

        $this->initializeAgain();
    }
}
