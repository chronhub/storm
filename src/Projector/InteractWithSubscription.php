<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Closure;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextRead;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\PersistentState;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function is_array;

trait InteractWithSubscription
{
    public ?string $currentStreamName = null;

    public ProjectionStatus $status = ProjectionStatus::IDLE;

    protected readonly PersistentState $state;

    protected readonly Sprint $sprint;

    protected ?ContextBuilder $context = null;

    public function compose(ContextBuilder $context, ProjectorCaster $projectorCaster, bool $keepRunning): void
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

    public function context(): ContextRead
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

    public function state(): PersistentState
    {
        return $this->state;
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    protected function cast(ProjectorCaster $caster): void
    {
        $originalState = $this->context->castInitCallback($caster);

        $this->state->put($originalState);

        $this->context->castEventHandlers($caster);
    }
}
