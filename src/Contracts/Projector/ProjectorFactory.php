<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

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
     * @see ContextReader::subscribeToStream()
     */
    public function subscribeToStream(string ...$streams): static;

    /**
     * Proxy method to set the categories.
     *
     * @see ContextReader::subscribeToCategory()
     */
    public function subscribeToCategory(string ...$categories): static;

    /**
     * Proxy method to set all streams.
     *
     * @see Context::subscribeToAll()
     */
    public function subscribeToAll(): static;

    /**
     * Proxy method to set the reactos.
     *
     * @param Closure(TWhen): ?TInit $reactors
     *
     * @see Context::when()
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the stop when.
     *
     * @see Context::haltOn()
     */
    public function haltOn(Closure $haltOn): static;

    /**
     * Proxy method to set the query filter.
     *
     * @see Context::withQueryFilter()
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to keep the state in memory.
     *
     * @see Context::withKeepState()
     */
    public function withKeepState(): static;

    /**
     * Proxy method to set the projector id.
     *
     * @see Context::withId()
     */
    public function withId(string $id): static;
}
