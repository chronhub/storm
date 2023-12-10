<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface QuerySubscriber extends Subscriber
{
    /**
     * Resets the stream positions nad user state.
     */
    public function resets(): void;
}
