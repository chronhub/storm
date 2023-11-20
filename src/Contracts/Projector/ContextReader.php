<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Closure;
use DateInterval;

interface ContextReader
{
    /**
     * Get the callback to initialize the state.
     */
    public function userState(): ?Closure;

    /**
     * Get the event handlers as array to be called when an event is received.
     */
    public function reactors(): callable;

    /**
     * Get stream names handled by the projection.
     */
    public function queries(): array;

    /**
     * Get the query filter to filter events.
     */
    public function queryFilter(): QueryFilter;

    /**
     * Get the timer interval to run the projection.
     */
    public function timer(): ?DateInterval;
}
