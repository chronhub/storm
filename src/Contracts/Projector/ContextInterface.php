<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;
use DateInterval;

interface ContextInterface extends ContextReader
{
    /**
     * Sets the callback to initialize the state.
     *
     * @example $context->initialize(fn(): array => ['count' => 0]);
     */
    public function initialize(Closure $initState): self;

    /**
     * Sets the streams to fetch events from.
     */
    public function fromStreams(string ...$streamNames): self;

    /**
     * Sets the categories to fetch events from.
     */
    public function fromCategories(string ...$categories): self;

    /**
     * Sets to fetch events from all streams
     */
    public function fromAll(): self;

    /**
     * Sets the event handlers to be called when an event is received.
     *
     * @example $context->when([fn(someEvent, array $state): array|void { ... }], ...);
     * @example $context->when(fn(someEvent, array $state): array|void { ... });
     */
    public function when(array|Closure $eventHandlers): self;

    /**
     * Sets the query filter to filter events.
     * A Projection query filter is mandatory when
     * the projection is persistent
     */
    public function withQueryFilter(QueryFilter $queryFilter): self;

    /**
     * Sets the timer interval to run the projection.
     *
     * Note that it could not stop projection at the exact time wanted
     * as projection should stop gracefully
     *
     * @param DateInterval|string|int $interval int in seconds or valid string interval or DateInterval
     */
    public function until(DateInterval|string|int $interval): self;
}
