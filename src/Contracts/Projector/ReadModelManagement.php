<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelManagement extends PersistentManagement
{
    /**
     * Get the read model instance.
     */
    public function getReadModel(): ReadModel;
}
