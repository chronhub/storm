<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Closure;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function is_array;

trait InteractWithSubscription
{
    public ?string $currentStreamName = null;

    public readonly bool $isPersistent;

    protected ContextInterface $context;

    public ProjectionStatus $status = ProjectionStatus::IDLE;

    protected readonly ProjectionStateInterface $state;

    protected readonly Sprint $sprint;

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

    protected function cast(Caster $caster): void
    {
        $originalState = $this->context->castInitCallback($caster);

        $this->state->put($originalState);

        $this->context->castEventHandlers($caster);
    }
}
