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
use function mb_strtoupper;
use function sprintf;

final class Context implements ContextInterface
{
    private array|Closure|null $eventHandlers = null;

    private ?QueryFilter $queryFilter = null;

    private ?Closure $initState = null;

    private array $queries = [];

    private null|int|DateInterval $timer = null;

    public function initialize(Closure $initState): self
    {
        $this->assertNotAlreadyInitialized();

        $this->assertNotStaticClosure($initState);

        $this->initState = $initState;

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): self
    {
        $this->assertQueryFilterNotSet();

        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function until(DateInterval|string|int $interval): ContextInterface
    {
        $this->assertTimerNotSet();

        $this->timer = $this->normalizeInterval($interval);

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

    public function when(array|Closure $eventHandlers): self
    {
        $this->assertEventHandlersNotSet();

        $this->validateEventHandlers($eventHandlers);

        $this->eventHandlers = $eventHandlers;

        return $this;
    }

    public function initCallback(): ?Closure
    {
        return $this->initState;
    }

    public function eventHandlers(): callable
    {
        $this->assertEventHandlersSet();

        return $this->createEventHandlers();
    }

    public function queries(): array
    {
        $this->assertQueriesSet();

        return $this->queries;
    }

    public function queryFilter(): QueryFilter
    {
        $this->assertQueryFilterSet();

        return $this->queryFilter;
    }

    public function timer(): ?DateInterval
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
            $this->bindEventHandlers($caster);
        }
    }

    /**
     * @internal
     */
    public function castInitCallback(Caster $caster): array
    {
        if ($this->initState instanceof Closure) {
            $callback = Closure::bind($this->initState, $caster, Caster::class);

            $result = $callback();

            $this->initState = $callback;

            return $result;
        }

        return [];
    }

    private function assertNotAlreadyInitialized(): void
    {
        if ($this->initState instanceof Closure) {
            throw new InvalidArgumentException('Projection already initialized');
        }
    }

    private function assertQueryFilterNotSet(): void
    {
        if ($this->queryFilter instanceof QueryFilter) {
            throw new InvalidArgumentException('Projection query filter already set');
        }
    }

    private function assertTimerNotSet(): void
    {
        if ($this->timer !== null) {
            throw new InvalidArgumentException('Projection timer already set');
        }
    }

    private function normalizeInterval(DateInterval|string|int $interval): DateInterval
    {
        if (is_int($interval)) {
            return new DateInterval(sprintf('PT%dS', $interval));
        }

        if (is_string($interval)) {
            return new DateInterval(mb_strtoupper($interval));
        }

        return $interval;
    }

    private function validateEventHandlers(array|Closure $eventHandlers): void
    {
        if (is_array($eventHandlers)) {
            foreach ($eventHandlers as $eventHandler) {
                $this->assertNotStaticClosure($eventHandler);
            }
        } else {
            $this->assertNotStaticClosure($eventHandlers);
        }
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

    private function assertEventHandlersSet(): void
    {
        if ($this->eventHandlers === null) {
            throw new InvalidArgumentException('Projection event handlers not set');
        }
    }

    private function createEventHandlers(): callable
    {
        if (is_array($this->eventHandlers)) {
            return new ProcessArrayEvent($this->eventHandlers);
        }

        return new ProcessClosureEvent($this->eventHandlers);
    }

    private function assertQueriesSet(): void
    {
        if ($this->queries === []) {
            throw new InvalidArgumentException('Projection streams all|names|categories not set');
        }
    }

    private function assertQueryFilterSet(): void
    {
        if (! $this->queryFilter instanceof QueryFilter) {
            throw new InvalidArgumentException('Projection query filter not set');
        }
    }

    private function bindEventHandlers(Caster $caster): void
    {
        foreach ($this->eventHandlers as &$eventHandler) {
            $eventHandler = Closure::bind($eventHandler, $caster, Caster::class);
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
