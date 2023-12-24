<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateInterval;

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
     * @throws InvalidArgumentException When user state is a static closure
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
     * @throws InvalidArgumentException When reactors is a static closure
     */
    public function when(Closure $reactors): self;

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
     * Sets the timer interval to run the projection when it runs in the background.
     *
     * Note that it could not stop projection at the exact time wanted
     * as projection should stop gracefully.
     * Zero int means that projection will run only once.
     *
     * @param DateInterval|string|int<0,max> $interval int in seconds, a valid string interval or DateInterval
     *
     * @throws InvalidArgumentException When the timer is already set
     */
    public function until(DateInterval|string|int $interval): self;
}
