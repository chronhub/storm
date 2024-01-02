<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Cycle;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsFirstCycle
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->cycle()->isFirstCycle();
    }
}
