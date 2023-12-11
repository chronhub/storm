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
     * @see ContextReaderInterface::initialize()
     */
    public function initialize(Closure $userState): static;

    /**
     * Proxy method to set the streams.
     *
     * @see ContextReaderInterface::fromStreams()
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Proxy method to set the categories.
     *
     * @see ContextReaderInterface::fromCategories()
     */
    public function fromCategories(string ...$categories): static;

    /**
     * Proxy method to set all streams.
     *
     * @see ContextInterface::fromAll()
     */
    public function fromAll(): static;

    /**
     * Proxy method to set the reactos.
     *
     * @param Closure(TWhen): ?TInit $reactors
     *
     * @see ContextInterface::when()
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the query filter.
     *
     * @see ContextInterface::withQueryFilter()
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to set the timer interval.
     *
     * @param DateInterval|string|int<0,max> $interval
     *
     * @see ContextInterface::until()
     */
    public function until(DateInterval|string|int $interval): static;

    /**
     * Proxy method to set the projector scope.
     *
     * @see ContextInterface::withScope()
     */
    public function withScope(Closure $scope): static;

    /**
     * Proxy method to keep the state in memory.
     *
     * @see ContextInterface::withKeepState()
     */
    public function withKeepState(): static;
}
