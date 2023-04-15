<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

/**
 * @template-covariant TItem of DomainEvent|object
 */
interface ProjectorFactory extends Projector
{
    /**
     * Proxy method to initialize the state.
     *
     * @see ContextInterface::initialize()
     *
     * @return $this
     */
    public function initialize(Closure $initCallback): static;

    /**
     * Proxy method to set the streams to fetch events from.
     *
     * @see ContextInterface::fromStreams()
     *
     * @return $this
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Proxy method to set the categories to fetch events from.
     *
     * @see ContextInterface::fromCategories()
     *
     * @return $this
     */
    public function fromCategories(string ...$categories): static;

    /**
     * Proxy method to set to fetch events from all streams.
     *
     * @see ContextInterface::fromAll()
     *
     * @return $this
     */
    public function fromAll(): static;

    /**
     * Proxy method to set the event handlers as array to be called when an event is received.
     *
     * @template T of Closure(TItem): void|Closure(TItem, array): array
     *
     * @param  array<T> $eventsHandlers
     * @return $this
     */
    public function when(array $eventsHandlers): static;

    /**
     * Proxy method to set the event handlers as Closure to be called when an event is received.
     *
     * @template T of Closure(TItem): void|Closure(TItem, array): array
     *
     * @phpstan-param  T $eventsHandlers
     *
     * @return $this
     */
    public function whenAny(Closure $eventsHandlers): static;

    /**
     * Proxy method to set the query filter to filter events.
     *
     * @see ContextInterface::withQueryFilter()
     *
     * @return $this
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;
}
