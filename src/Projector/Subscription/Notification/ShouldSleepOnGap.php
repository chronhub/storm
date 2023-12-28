<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class ShouldSleepOnGap
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        if ($subscriptor->recognition()->hasGap()) {
            $subscriptor->recognition()->sleepWhenGap();

            return true;
        }

        return false;
    }
}
