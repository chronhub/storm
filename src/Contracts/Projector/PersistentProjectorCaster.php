<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Reporter\DomainEvent;

interface PersistentProjectorCaster extends ProjectorCaster
{
    /**
     * Link event to the underlying projection stream
     */
    public function linkTo(string $streamName, DomainEvent $event): void;

    /**
     * Emit event to a new stream
     */
    public function emit(DomainEvent $event): void;
}
