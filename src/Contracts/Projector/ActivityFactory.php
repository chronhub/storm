<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Subscription\Subscription;

interface ActivityFactory
{
    public function __invoke(
        Subscription $subscription,
        ?PersistentManagement $management,
        ProjectorScope $scope
    ): array;
}
