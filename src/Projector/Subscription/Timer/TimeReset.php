<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Timer;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class TimeReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->time()->reset();
    }
}
