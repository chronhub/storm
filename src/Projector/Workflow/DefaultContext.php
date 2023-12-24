<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Provider\EventStream\DiscoverAllStream;
use Chronhub\Storm\Projector\Provider\EventStream\DiscoverCategories;
use Chronhub\Storm\Projector\Provider\EventStream\DiscoverStreams;
use Closure;
use DateInterval;

use function is_int;
use function is_string;
use function mb_strtoupper;
use function sprintf;

final class DefaultContext implements ContextReader
{
    /**
     * @var ?callable(EventStreamProvider): array<string|empty>
     */
    private $query;

    private ?Closure $userState = null;

    private ?Closure $reactors = null;

    private ?QueryFilter $queryFilter = null;

    private ?Closure $userScope = null;

    private DateInterval|string|int|null $timer = null;

    private bool $keepState = false;

    public function initialize(Closure $userState): self
    {
        if ($this->userState instanceof Closure) {
            throw new InvalidArgumentException('Projection already initialized');
        }

        $this->userState = Closure::bind($userState, $this);

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

    public function withScope(Closure $scope): self
    {
        if ($this->userScope instanceof Closure) {
            throw new InvalidArgumentException('Projection scope already set');
        }

        $this->userScope = $scope;

        return $this;
    }

    public function withKeepState(): self
    {
        if ($this->keepState === true) {
            throw new InvalidArgumentException('Projection keep state already set');
        }

        $this->keepState = true;

        return $this;
    }

    public function until(DateInterval|string|int $interval): self
    {
        if ($this->timer !== null) {
            throw new InvalidArgumentException('Projection timer already set');
        }

        $this->timer = $this->normalizeInterval($interval);

        return $this;
    }

    public function subscribeToStream(string ...$streamNames): self
    {
        $this->assertQueriesNotSet();

        $this->query = new DiscoverStreams($streamNames);

        return $this;
    }

    public function subscribeToCategory(string ...$categories): self
    {
        $this->assertQueriesNotSet();

        $this->query = new DiscoverCategories($categories);

        return $this;
    }

    public function subscribeToAll(): self
    {
        $this->assertQueriesNotSet();

        $this->query = new DiscoverAllStream();

        return $this;
    }

    public function when(Closure $reactors): self
    {
        if ($this->reactors !== null) {
            throw new InvalidArgumentException('Projection reactors already set');
        }

        $this->reactors = $reactors;

        return $this;
    }

    public function userState(): ?Closure
    {
        return $this->userState;
    }

    public function userScope(): ?Closure
    {
        return $this->userScope;
    }

    public function reactors(): Closure
    {
        if ($this->reactors === null) {
            throw new InvalidArgumentException('Projection reactors not set');
        }

        return $this->reactors;
    }

    public function queries(): callable
    {
        if ($this->query === null) {
            throw new InvalidArgumentException('Projection streams all|names|categories not set');
        }

        return $this->query;
    }

    public function queryFilter(): QueryFilter
    {
        if ($this->queryFilter === null) {
            throw new InvalidArgumentException('Projection query filter not set');
        }

        return $this->queryFilter;
    }

    public function keepState(): bool
    {
        return $this->keepState;
    }

    public function timer(): ?DateInterval
    {
        return $this->timer;
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
        if ($this->query !== null) {
            throw new InvalidArgumentException('Projection streams all|names|categories already set');
        }
    }
}
