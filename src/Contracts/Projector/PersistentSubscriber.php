<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentSubscriber extends Subscriber
{
    /**
     * Reset the projection.
     */
    public function reset(): void;

    /**
     * Return the name of the persistent projection name.
     */
    public function getName(): string;
}
