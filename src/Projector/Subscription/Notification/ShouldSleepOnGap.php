<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class ShouldSleepOnGap
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        if ($subscriptor->streamManager()->hasGap()) {
            $subscriptor->streamManager()->sleepWhenGap();

            return true;
        }

        return false;
    }
}
