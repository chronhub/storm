<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Timer;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CurrentTime
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->time()->getCurrentTime();
    }
}
