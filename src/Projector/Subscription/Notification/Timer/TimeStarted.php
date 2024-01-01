<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Timer;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class TimeStarted
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->time()->start();
    }
}
