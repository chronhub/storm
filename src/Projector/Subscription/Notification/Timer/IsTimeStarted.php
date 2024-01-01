<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Timer;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsTimeStarted
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->time()->isStarted();
    }
}
