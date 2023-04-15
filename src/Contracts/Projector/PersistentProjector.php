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
     * Get the stream name.
     */
    public function getStreamName(): string;
}
