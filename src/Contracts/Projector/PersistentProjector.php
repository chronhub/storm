<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentProjector extends PersistentProjectorFactory
{
    /**
     * Get the projection name.
     *
     * Projection name is a substitute for stream name
     */
    public function getName(): string;

    /**
     * Reset the projection.
     */
    public function reset(): void;

    /**
     * Delete the projection.
     */
    public function delete(bool $deleteEmittedEvents): void;
}
