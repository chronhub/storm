<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;

interface Subscriber
{
    /**
     * Start subscription
     */
    public function start(ContextReader $context, bool $keepRunning): void;

    /**
     * Interact with notification hub
     */
    public function interact(Closure $callback): mixed;
}
