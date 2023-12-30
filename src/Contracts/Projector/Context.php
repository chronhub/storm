<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

/**
 * @template TInit of array
 * @template TWhen of array{DomainEvent,TInit,ProjectorScope}|array<DomainEvent,ProjectorScope>
 */
interface Context
{
    /**
     * Sets the optional callback to initialize the state.
     *
     * @param Closure():TInit $userState
     *
     * @throws InvalidArgumentException When user state is already set
     *
     * @example $context->initialize(fn(): array => ['count' => 0]);
     */
    public function initialize(Closure $userState): self;

    /**
     * Sets the streams to fetch events from.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToStream(string ...$streamNames): self;

    /**
     * Sets the categories to fetch events from.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToCategory(string ...$categories): self;

    /**
     * Sets to fetch events from all streams
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToAll(): self;

    /**
     * Sets the event handlers to be called when an event is received.
     *
     * @param Closure(TWhen): ?TInit $reactors
     *
     * @throws InvalidArgumentException When reactors are already set
     */
    public function when(Closure $reactors): self;

    /**
     * Stop the projection when a condition is met.
     *
     * @return $this
     */
    public function haltOn(Closure $haltOn): self;

    /**
     * Sets the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is already set
     */
    public function withQueryFilter(QueryFilter $queryFilter): self;

    /**
     * Keep the state in memory for the next run.
     *
     * Only available for query projection.
     * When not set, the state will be reset at each run.
     * Also, user state must be initialized.
     */
    public function withKeepState(): self;

    /**
     * Set a projection id to identify it.
     *
     * Note that a default id will be provided but uniqueness is not guaranteed
     */
    public function withId(string $id): self;
}
