<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Reporter\DomainEvent;

interface PersistentProjectorCaster extends ProjectorCaster
{
    public function emit(DomainEvent $event): void;

    public function linkTo(string $streamName, DomainEvent $event): void;
}
