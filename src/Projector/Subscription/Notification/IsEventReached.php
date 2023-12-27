<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsEventReached
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->eventCounter()->isReached();
    }
}
