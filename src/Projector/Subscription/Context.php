<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Closure;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Projector\Scheme\ProcessArrayEvent;
use Chronhub\Storm\Projector\Scheme\ProcessClosureEvent;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function count;
use function is_array;

final class Context implements ContextBuilder
{
    protected array|Closure|null $eventHandlers = null;

    protected ?QueryFilter $queryFilter = null;

    protected ?Closure $initCallback = null;

    protected array $queries = [];

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

    public function initCallback(): ?Closure
    {
        return $this->initCallback;
    }

    public function eventHandlers(): callable
    {
        if ($this->eventHandlers === null) {
            throw new InvalidArgumentException('Projection event handlers not set');
        }

        if (is_array($this->eventHandlers)) {
            return new ProcessArrayEvent($this->eventHandlers);
        }

        return new ProcessClosureEvent($this->eventHandlers);
    }

    public function queries(): array
    {
        if (empty($this->queries)) {
            throw new InvalidArgumentException('Projection streams all|names|categories not set');
        }

        return $this->queries;
    }

    public function queryFilter(): QueryFilter
    {
        if ($this->queryFilter === null) {
            throw new InvalidArgumentException('Projection query filter not set');
        }

        return $this->queryFilter;
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
