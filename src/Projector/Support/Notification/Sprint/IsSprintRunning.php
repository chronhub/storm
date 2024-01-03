<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Sprint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsSprintRunning
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->sprint()->inProgress();
    }
}
