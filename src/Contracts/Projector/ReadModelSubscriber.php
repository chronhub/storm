<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelSubscriber extends PersistentSubscriber
{
    /**
     * Delete the projection with or without emitted events
     */
    public function delete(bool $withReadModel): void;
}
