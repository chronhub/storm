<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Sprint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsSprintDaemonize
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->sprint()->inBackground();
    }
}
