<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Reporter\DomainEvent;

interface PersistentProjectorCaster extends ProjectorCaster
{
    /**
     * Link event to the underlying projection stream
     *
     * @param  string  $streamName
     * @param  DomainEvent  $event
     * @return void
     */
    public function linkTo(string $streamName, DomainEvent $event): void;

    /**
     * Emit event to a new stream
     *
     * @param  DomainEvent  $event
     * @return void
     */
    public function emit(DomainEvent $event): void;
}
