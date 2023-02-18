<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Closure;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\PersistentState;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;
use Chronhub\Storm\Contracts\Projector\ProjectorOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function count;
use function is_array;

/**
 * @final
 */
class Context
{
    /**
     * @var array|callable|null
     */
    protected $eventHandlers = null;

    protected ?QueryFilter $queryFilter = null;

    protected array $queries = [];

    public Closure|null $initCallback = null;

    public ?string $currentStreamName = null;

    /** Only for persistent projection no read model */
    public bool $isStreamCreated = false;

    public ProjectionStatus $status = ProjectionStatus::IDLE;

    public readonly PersistentState $state;

    public readonly Runner $runner;

    public readonly bool $isPersistent;

    public function __construct(public readonly ProjectorOption $option,
                                public readonly StreamPosition $streamPosition,
                                public readonly ?EventCounter $eventCounter = null,
                                public readonly ?DetectGap $gap = null)
    {
        $this->state = new ProjectionState();
        $this->runner = new Runner();
        $this->isPersistent = $eventCounter instanceof EventCounter;
    }

    public function compose(ProjectorCaster $projectorCaster, bool $runInBackground): void
    {
        $this->runner->runInBackground($runInBackground);

        $this->cast($projectorCaster);
    }

    public function resetStateWithInitialize(): void
    {
        $this->state->reset();

        $callback = $this->initCallback;

        if ($callback instanceof Closure) {
            $state = $callback();

            if (is_array($state)) {
                $this->state->put($state);
            }
        }
    }

    public function initialize(Closure $initCallback): self
    {
        if ($this->initCallback !== null) {
            throw new InvalidArgumentException('Projection already initialized');
        }

        $this->initCallback = $initCallback;

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): self
    {
        if ($this->queryFilter !== null) {
            throw new InvalidArgumentException('Projection query filter already set');
        }

        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function fromStreams(string ...$streamNames): self
    {
        $this->assertQueriesNotSet();

        $this->queries['names'] = $streamNames;

        return $this;
    }

    public function fromCategories(string ...$categories): self
    {
        $this->assertQueriesNotSet();

        $this->queries['categories'] = $categories;

        return $this;
    }

    public function fromAll(): self
    {
        $this->assertQueriesNotSet();

        $this->queries['all'] = true;

        return $this;
    }

    public function when(array $eventHandlers): self
    {
        $this->assertEventHandlersNotSet();

        $this->eventHandlers = $eventHandlers;

        return $this;
    }

    public function whenAny(callable $eventHandler): self
    {
        $this->assertEventHandlersNotSet();

        $this->eventHandlers = $eventHandler;

        return $this;
    }

    public function eventHandlers(): callable
    {
        if (is_array($this->eventHandlers)) {
            return new ProcessArrayEvent($this->eventHandlers);
        }

        return new ProcessClosureEvent($this->eventHandlers);
    }

    public function queries(): array
    {
        return $this->queries;
    }

    public function queryFilter(): QueryFilter
    {
        return $this->queryFilter;
    }

    protected function cast(ProjectorCaster $caster): void
    {
        $this->validate();

        $initState = $this->castInitCallback($caster);

        $this->state->put($initState);

        $this->castEventHandlers($caster);
    }

    protected function validate(): void
    {
        if (count($this->queries) === 0) {
            throw new InvalidArgumentException('Projection streams all|names|categories not set');
        }

        if ($this->eventHandlers === null) {
            throw new InvalidArgumentException('Projection event handlers not set');
        }

        if ($this->queryFilter === null) {
            throw new InvalidArgumentException('Projection query filter not set');
        }

        if ($this->isPersistent && ! $this->queryFilter instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Persistent projector require a projection query filter');
        }
    }

    protected function castEventHandlers(ProjectorCaster $caster): void
    {
        if ($this->eventHandlers instanceof Closure) {
            $this->eventHandlers = Closure::bind($this->eventHandlers, $caster);
        } else {
            foreach ($this->eventHandlers as &$eventHandler) {
                $eventHandler = Closure::bind($eventHandler, $caster);
            }
        }
    }

    protected function castInitCallback(ProjectorCaster $caster): array
    {
        if ($this->initCallback instanceof Closure) {
            $callback = Closure::bind($this->initCallback, $caster);

            $result = $callback();

            $this->initCallback = $callback;

            return $result;
        }

        return [];
    }

    protected function assertQueriesNotSet(): void
    {
        if (count($this->queries) > 0) {
            throw new InvalidArgumentException('Projection streams all|names|categories already set');
        }
    }

    protected function assertEventHandlersNotSet(): void
    {
        if ($this->eventHandlers !== null) {
            throw new InvalidArgumentException('Projection event handlers already set');
        }
    }
}
