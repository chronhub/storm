<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentProjector extends ProjectorBuilder
{
    /**
     * Delete projection with or without emitted events
     *
     * @param  bool  $withEmittedEvents
     * @return void
     */
    public function delete(bool $withEmittedEvents): void;

    /**
     * Get current stream name
     *
     * @return string
     */
    public function getStreamName(): string;
}
