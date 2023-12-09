<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelSubscriber extends PersistentSubscriber
{
    /**
     * Return the read model instance.
     */
    public function readModel(): ReadModel;
}
