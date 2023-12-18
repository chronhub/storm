<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateInterval;

/**
 * @template TInit of array
 * @template TWhen of array{DomainEvent,TInit,ProjectorScope}|array<DomainEvent,ProjectorScope>
 */
interface ProjectorFactory extends Projector
{
    /**
     * Proxy method to initialize the state.
     *
     * @param Closure():TInit $userState
     *
     * @see ContextReader::initialize()
     */
    public function initialize(Closure $userState): static;

    /**
     * Proxy method to set the streams.
     *
     * @see ContextReader::fromStreams()
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Proxy method to set the categories.
     *
     * @see ContextReader::fromCategories()
     */
    public function fromCategories(string ...$categories): static;

    /**
     * Proxy method to set all streams.
     *
     * @see Context::fromAll()
     */
    public function fromAll(): static;

    /**
     * Proxy method to set the reactos.
     *
     * @param Closure(TWhen): ?TInit $reactors
     *
     * @see Context::when()
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the query filter.
     *
     * @see Context::withQueryFilter()
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to set the timer interval.
     *
     * @param DateInterval|string|int<0,max> $interval
     *
     * @see Context::until()
     */
    public function until(DateInterval|string|int $interval): static;

    /**
     * Proxy method to set the projector scope.
     *
     * @see Context::withScope()
     */
    public function withScope(Closure $scope): static;

    /**
     * Proxy method to keep the state in memory.
     *
     * @see Context::withKeepState()
     */
    public function withKeepState(): static;
}
