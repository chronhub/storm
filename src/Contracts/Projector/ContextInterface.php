<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Closure;
use DateInterval;

interface ContextInterface
{
    /**
     * Sets the optional callback to initialize the state.
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
     * @throws InvalidArgumentException When streams is already set
     * @throws InvalidArgumentException When streams is empty
     */
    public function fromStreams(string ...$streamNames): self;

    /**
     * Sets the categories to fetch events from.
     *
     * @throws InvalidArgumentException When streams is already set
     * @throws InvalidArgumentException When streams is empty
     */
    public function fromCategories(string ...$categories): self;

    /**
     * Sets to fetch events from all streams
     *
     * @throws InvalidArgumentException When streams is already set
     * @throws InvalidArgumentException When streams is empty
     */
    public function fromAll(): self;

    /**
     * Sets the event handlers to be called when an event is received.
     *
     * @throws InvalidArgumentException When reactors is already set
     * @throws InvalidArgumentException When reactors is a static closure
     *
     * @example $context->when(fn(someEvent, array $state): array|void { ... });
     */
    public function when(Closure $reactors): self;

    /**
     * Sets the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is already set
     */
    public function withQueryFilter(QueryFilter $queryFilter): self;

    /**
     * Sets the timer interval to run the projection.
     *
     * Note that it could not stop projection at the exact time wanted
     * as projection should stop gracefully
     *
     * @param DateInterval|string|int $interval int in seconds or valid string interval or DateInterval
     *
     * @throws InvalidArgumentException When timer is already set
     */
    public function until(DateInterval|string|int $interval): self;
}
