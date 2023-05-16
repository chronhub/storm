<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Closure;
use DateInterval;
use ReflectionFunction;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function strtoupper;

final class Context implements ContextInterface
{
    private array|Closure|null $eventHandlers = null;

    private ?QueryFilter $queryFilter = null;

    private ?Closure $initCallback = null;

    private array $queries = [];

    private null|int|DateInterval $timer = null;

    public function initialize(Closure $initCallback): self
    {
        if ($this->initCallback instanceof Closure) {
            throw new InvalidArgumentException('Projection already initialized');
        }

        $this->assertNotStaticClosure($initCallback);

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

    public function until(DateInterval|string|int $interval): ContextInterface
    {
        if ($this->timer !== null) {
            throw new InvalidArgumentException('Projection timer already set');
        }

        if (is_int($interval)) {
            $interval = new DateInterval(sprintf('PT%dS', $interval));
        }

        if (is_string($interval)) {
            $interval = new DateInterval(strtoupper($interval));
        }

       $this->timer = $interval;

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

        foreach ($eventHandlers as $eventHandler) {
            $this->assertNotStaticClosure($eventHandler);
        }

        $this->eventHandlers = $eventHandlers;

        return $this;
    }

    public function whenAny(callable $eventHandler): self
    {
        $this->assertEventHandlersNotSet();

        $this->assertNotStaticClosure($eventHandler);

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

    public function timer(): null|DateInterval
    {
        return $this->timer;
    }

    /**
     * @internal
     */
    public function castEventHandlers(Caster $caster): void
    {
        if ($this->eventHandlers instanceof Closure) {
            $this->eventHandlers = Closure::bind($this->eventHandlers, $caster, Caster::class);
        } else {
            foreach ($this->eventHandlers as &$eventHandler) {
                $eventHandler = Closure::bind($eventHandler, $caster, Caster::class);
            }
        }
    }

    /**
     * @internal
     */
    public function castInitCallback(Caster $caster): array
    {
        if ($this->initCallback instanceof Closure) {
            $callback = Closure::bind($this->initCallback, $caster, Caster::class);

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

    private function assertNotStaticClosure(Closure $callback): void
    {
        $reflection = new ReflectionFunction($callback);

        if ($reflection->isStatic()) {
            throw new InvalidArgumentException('Static closure is not allowed');
        }
    }
}
