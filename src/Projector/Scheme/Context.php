<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Closure;
use DateInterval;

use function is_int;
use function is_string;
use function mb_strtoupper;
use function sprintf;

final class Context implements ContextReaderInterface
{
    private array $queries = [];

    private ?Closure $userState = null;

    private ?Closure $reactors = null;

    private ?QueryFilter $queryFilter = null;

    private DateInterval|string|int|null $timer = null;

    private ?Closure $userScope = null;

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

    public function until(DateInterval|string|int $interval): self
    {
        if ($this->timer !== null) {
            throw new InvalidArgumentException('Projection timer already set');
        }

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

    public function queries(): array
    {
        if ($this->queries === []) {
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
        if ($this->queries !== []) {
            throw new InvalidArgumentException('Projection streams all|names|categories already set');
        }
    }
}
