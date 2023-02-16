<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentProjector extends ProjectorBuilder
{
    /**
     * Delete projection with or without emitted events
     */
    public function delete(bool $withEmittedEvents): void;

    /**
     * Get current stream name
     */
    public function getStreamName(): string;
}
