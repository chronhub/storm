<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateInterval;

/**
 * @template TState of array
 * @template TReactor of array<DomainEvent, TState>|array<DomainEvent>
 */
interface ContextReaderInterface extends ContextInterface
{
    /**
     * Get the callback to initialize the state.
     *
     * @return Closure():TState|null
     */
    public function userState(): ?Closure;

    /**
     * Get the event handlers as an array to be called when an event is received.
     *
     * @return Closure(TReactor): ?TState $reactors
     *
     * @throws InvalidArgumentException When reactors are not set
     */
    public function reactors(): Closure;

    /**
     * Get stream names handled by the projection.
     *
     * @throws InvalidArgumentException When queries are not set
     */
    public function queries(): array;

    /**
     * Get the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is not set
     */
    public function queryFilter(): QueryFilter;

    /**
     * Get the timer interval to run the projection.
     */
    public function timer(): ?DateInterval;

    /**
     * Get the projection scope.
     *
     * When not set, a default scope will be used.
     */
    public function userScope(): ?Closure;
}
