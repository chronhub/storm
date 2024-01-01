<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Sprint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsSprintTerminated
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return ! $subscriptor->watcher()->sprint()->inBackground()
            || ! $subscriptor->watcher()->sprint()->inProgress();
    }
}
