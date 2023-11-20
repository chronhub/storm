<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Closure;
use DateInterval;
use ReflectionFunction;

use function is_int;
use function is_string;
use function mb_strtoupper;
use function sprintf;

final class Context implements ContextInterface
{
    private array $queries = [];

    private ?Closure $userState = null;

    private array|Closure|null $reactors = null;

    private ?QueryFilter $queryFilter = null;

    private null|int|DateInterval $timer = null;

    public function initialize(Closure $userState): self
    {
        $this->assertNotAlreadyInitialized();

        $this->assertNotStaticClosure($userState);

        $this->userState = $userState;

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

    public function when(Closure $reactors): self
    {
        $this->assertReactorsNotSet();

        $this->assertNotStaticClosure($reactors);

        $this->reactors = $reactors;

        return $this;
    }

    public function userState(): ?Closure
    {
        return $this->userState;
    }

    public function reactors(): callable
    {
        $this->assertReactorsSet();

        return new EventProcessor($this->reactors);
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
    public function bindReactors(ProjectorScope $projectionScope): void
    {
        if ($this->reactors instanceof Closure) {
            $this->reactors = Closure::bind($this->reactors, $projectionScope, ProjectorScope::class);
        }
    }

    /**
     * @internal
     */
    public function bindUserState(ProjectorScope $scope): array
    {
        if ($this->userState instanceof Closure) {
            $callback = Closure::bind($this->userState, $scope, ProjectorScope::class);

            $result = $callback();

            $this->userState = $callback;

            return $result;
        }

        return [];
    }

    private function assertNotAlreadyInitialized(): void
    {
        if ($this->userState instanceof Closure) {
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

    private function assertQueriesNotSet(): void
    {
        if ($this->queries !== []) {
            throw new InvalidArgumentException('Projection streams all|names|categories already set');
        }
    }

    private function assertReactorsNotSet(): void
    {
        if ($this->reactors !== null) {
            throw new InvalidArgumentException('Projection reactors already set');
        }
    }

    private function assertReactorsSet(): void
    {
        if ($this->reactors === null) {
            throw new InvalidArgumentException('Projection reactors not set');
        }
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

    private function assertNotStaticClosure(Closure $callback): void
    {
        $reflection = new ReflectionFunction($callback);

        if ($reflection->isStatic()) {
            throw new InvalidArgumentException('Static closure is not allowed in user state and reactors');
        }
    }
}
