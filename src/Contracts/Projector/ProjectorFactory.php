<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateInterval;

/**
 * @template TInit of array
 * @template TWhen of array<DomainEvent,TInit>|array<DomainEvent>
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
     * Proxy method to set the streams to fetch events from.
     *
     * @see ContextReaderInterface::fromStreams()
     */
    public function fromStreams(string ...$streams): static;

    /**
     * Proxy method to set the categories to fetch events from.
     *
     * @see ContextReaderInterface::fromCategories()
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
     * @param Closure(TWhen): ?TInit $reactors
     *
     * @see ContextInterface::when()
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the query filter to filter events.
     *
     * @see ContextInterface::withQueryFilter()
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to set the timer interval.
     *
     * @param DateInterval|string|int $interval int in seconds, a valid string interval or DateInterval instance
     *
     * @see ContextInterface::withTimer()
     */
    public function withTimer(DateInterval|string|int $interval): static;
}
