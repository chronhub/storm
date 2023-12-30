<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CycleHasStarted
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->loop()->hasStarted();
    }
}
