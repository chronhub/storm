<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;
use DateInterval;

interface ProjectorFactory extends Projector
{
    /**
     * Proxy method to initialize the state.
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
     * @see ContextReaderInterface::fromAll()
     */
    public function fromAll(): static;

    /**
     * Proxy method to set the event handlers to be called when an event is received.
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the query filter to filter events.
     *
     * @see ContextReaderInterface::withQueryFilter()
     */
    public function withQueryFilter(QueryFilter $queryFilter): static;

    /**
     * Proxy method to set the timer interval.
     *
     * @param int|DateInterval $interval int in seconds or DateInterval
     */
    public function withTimer(int|DateInterval $interval): static;
}
