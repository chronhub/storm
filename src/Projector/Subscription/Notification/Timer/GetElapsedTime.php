<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Timer;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class GetElapsedTime
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->time()->getElapsedTime();
    }
}
