<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;

/**
 * @template TInit of array
 * @template TWhen of array{ProjectorScope}
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
     * @param Closure(TWhen): void $reactors
     *
     * @see Context::when()
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the stop watch callback.
     *
     * @see Context::haltOn()
     */
    public function haltOn(Closure $haltOn): static;

    /**
     * Proxy method to set the query filter.
     *
     * @see Context::withQueryFilter()
     */
    public function filter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to keep the state in memory.
     *
     * @see Context::withKeepState()
     */
    public function keepState(): static;

    /**
     * Proxy method to set the projector id.
     *
     * @see Context::withId()
     */
    public function describe(string $id): static;
}
