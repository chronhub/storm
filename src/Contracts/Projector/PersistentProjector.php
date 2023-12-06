<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentProjector extends ProjectorFactory
{
    /**
     * Get the projection name.
     *
     * Projection name is a substitute for stream name
     */
    public function getName(): string;
}
