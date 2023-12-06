<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentProjector extends ProjectorFactory
{
    /**
     * Delete the projection with or without emitted events.
     */
    public function delete(bool $withEmittedEvents): void;

    /**
     * Get the projection name.
     *
     * Projection name is a substitute for stream name
     */
    public function getName(): string;
}
