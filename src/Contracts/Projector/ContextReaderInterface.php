<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Closure;
use DateInterval;

interface ContextReaderInterface extends ContextInterface
{
    /**
     * Get the callback to initialize the state.
     */
    public function userState(): ?Closure;

    /**
     * Get the event handlers as array to be called when an event is received.
     *
     * @throws InvalidArgumentException When reactors is not set
     */
    public function reactors(): Closure;

    /**
     * Get stream names handled by the projection.
     *
     * @throws InvalidArgumentException When queries is not set
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
     * Bind projector scope to user state if initialized.
     */
    public function bindUserState(ProjectorScope $projectorScope): array;

    /**
     * Bind projector scope to reactors.
     */
    public function bindReactors(ProjectorScope $projectorScope): void;
}
