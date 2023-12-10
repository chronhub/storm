<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelSubscriptionManagement extends PersistentSubscriptionManagement
{
    public function getReadModel(): ReadModel;
}
