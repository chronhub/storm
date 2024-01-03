<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Cycle;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsCycleStarted
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->cycle()->hasStarted();
    }
}
