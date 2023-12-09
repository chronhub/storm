<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;

class SubscriptionBehaviour
{
    public function __construct(
        Beacon $beacon,
        ProjectionRepositoryInterface $repository
    )
    {

    }
}
