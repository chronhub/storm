<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

/**
 * @template TState of array|null
 * @template TReactor of array{DomainEvent,TState,ProjectorScope}|array<DomainEvent,ProjectorScope>
 */
interface ContextReader extends Context
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
     * @return Closure(TReactor): ?TState
     *
     * @throws InvalidArgumentException When reactors are not set
     */
    public function reactors(): Closure;

    /**
     * Get stream names handled by the projection.
     *
     * @return callable(EventStreamProvider): array<string|empty>
     *
     * @throws InvalidArgumentException When queries are not set
     */
    public function queries(): callable;

    /**
     * Get the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is not set
     */
    public function queryFilter(): QueryFilter;

    /**
     * Check if projection should keep state in memory.
     *
     * Default is false
     */
    public function keepState(): bool;

    /**
     * Get the projection identifier.
     */
    public function id(): ?string;

    /**
     * Get the condition to stop the projection.
     *
     * @return array<string,callable>
     */
    public function haltOnCallback(): array;
}
