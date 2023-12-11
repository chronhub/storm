<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface EmitterSubscriber extends PersistentSubscriber
{
    /**
     * Delete the projection with or without emitted events
     */
    public function delete(bool $withEmittedEvents): void;
}
