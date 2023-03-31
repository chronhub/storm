<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Closure;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\KeepRunning;
use Chronhub\Storm\Contracts\Projector\ContextRead;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\PersistentState;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function is_array;

trait ProvideSubscription
{
    public ?string $currentStreamName = null;

    public ProjectionStatus $status = ProjectionStatus::IDLE;

    public readonly PersistentState $state;

    public readonly KeepRunning $runner;

    protected ?ContextBuilder $context = null;

    public function compose(ContextBuilder $context, ProjectorCaster $projectorCaster, bool $runInBackground): void
    {
        $this->context = $context;

        if ($this->isPersistent && ! $this->context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Persistent subscription require a projection query filter');
        }

        $this->runner->runInBackground($runInBackground);

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

    protected function cast(ProjectorCaster $caster): void
    {
        $initState = $this->castInitCallback($caster);

        $this->state->put($initState);

        $this->castEventHandlers($caster);
    }

    protected function castEventHandlers(ProjectorCaster $caster): void
    {
        if ($this->context->eventHandlers instanceof Closure) {
            $this->context->eventHandlers = Closure::bind($this->context->eventHandlers, $caster);
        } else {
            foreach ($this->context->eventHandlers as &$eventHandler) {
                $eventHandler = Closure::bind($eventHandler, $caster);
            }
        }
    }

    protected function castInitCallback(ProjectorCaster $caster): array
    {
        if ($this->context->initCallback instanceof Closure) {
            $callback = Closure::bind($this->context->initCallback, $caster);

            $result = $callback();

            $this->context->initCallback = $callback;

            return $result;
        }

        return [];
    }
}
