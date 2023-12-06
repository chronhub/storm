<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelSubscriptionInterface extends PersistentSubscriptionInterface
{
    /**
     * Return the read model instance.
     */
    public function readModel(): ReadModel;
}
