<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Reporter\DomainEvent;

interface ProjectionProjector extends PersistentProjector
{
    /**
     * Emit event to a new stream
     */
    public function emit(DomainEvent $event): void;

    /**
     * Emit event to a new stream with the given name
     */
    public function linkTo(string $streamName, DomainEvent $event): void;
}
