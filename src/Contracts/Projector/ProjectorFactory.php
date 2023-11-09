<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateInterval;

/**
 * @template-covariant TItem of DomainEvent|object
 */
interface ProjectorFactory extends Projector
{
    /**
     * Proxy method to initialize the state.
     *
     * @see ContextInterface::initialize()
     */
    public function initialize(Closure $initCallback): static;

    /**
     * Proxy method to set the streams to fetch events from.
     *
     * @see ContextInterface::fromStreams()
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Proxy method to set the categories to fetch events from.
     *
     * @see ContextInterface::fromCategories()
     */
    public function fromCategories(string ...$categories): static;

    /**
     * Proxy method to set to fetch events from all streams.
     *
     * @see ContextInterface::fromAll()
     */
    public function fromAll(): static;

    /**
     * Proxy method to set the event handlers to be called when an event is received.
     *
     * @template T of Closure(TItem): void|Closure(TItem, array): array
     *
     * @param array<T> $eventsHandlers
     */
    public function when(array|Closure $eventsHandlers): static;

    /**
     * Proxy method to set the query filter to filter events.
     *
     * @see ContextInterface::withQueryFilter()
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to set the timer interval.
     *
     * @param int|DateInterval $interval int in seconds or DateInterval
     */
    public function withTimer(int|DateInterval $interval): static;
}
