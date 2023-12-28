<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\ProjectionStatus;

final class GetStatus
{
    public function __invoke(Subscriptor $subscriptor): ProjectionStatus
    {
        return $subscriptor->currentStatus();
    }
}