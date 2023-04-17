<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Closure;
use function is_array;

final class Context implements ContextInterface
{
    private array|Closure|null $eventHandlers = null;

    private ?QueryFilter $queryFilter = null;

    private ?Closure $initCallback = null;

    private array $queries = [];

    public function initialize(Closure $initCallback): self
    {
        if ($this->initCallback instanceof Closure) {
            throw new InvalidArgumentException('Projection already initialized');
        }

        $this->initCallback = $initCallback;

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): self
    {
        if ($this->queryFilter instanceof QueryFilter) {
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
        if ($this->queries === []) {
            throw new InvalidArgumentException('Projection streams all|names|categories not set');
        }

        return $this->queries;
    }

    public function queryFilter(): QueryFilter
    {
        if (! $this->queryFilter instanceof QueryFilter) {
            throw new InvalidArgumentException('Projection query filter not set');
        }

        return $this->queryFilter;
    }

    /**
     * @internal
     */
    public function castEventHandlers(Caster $caster): void
    {
        if ($this->eventHandlers instanceof Closure) {
            $this->eventHandlers = Closure::bind($this->eventHandlers, $caster);
        } else {
            foreach ($this->eventHandlers as &$eventHandler) {
                $eventHandler = Closure::bind($eventHandler, $caster);
            }
        }
    }

    /**
     * @internal
     */
    public function castInitCallback(Caster $caster): array
    {
        if ($this->initCallback instanceof Closure) {
            $callback = Closure::bind($this->initCallback, $caster);

            $result = $callback();

            $this->initCallback = $callback;

            return $result;
        }

        return [];
    }

    private function assertQueriesNotSet(): void
    {
        if ($this->queries !== []) {
            throw new InvalidArgumentException('Projection streams all|names|categories already set');
        }
    }

    private function assertEventHandlersNotSet(): void
    {
        if ($this->eventHandlers !== null) {
            throw new InvalidArgumentException('Projection event handlers already set');
        }
    }
}
