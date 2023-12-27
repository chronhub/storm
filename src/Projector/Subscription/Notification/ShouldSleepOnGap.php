<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\GapDetection;
use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class ShouldSleepOnGap
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        if (! $this->hasGap($subscriptor)) {
            return false;
        }

        $subscriptor->batchStreamsAware()->sleep();

        return true;
    }

    private function hasGap(Subscriptor $subscriptor): bool
    {
        if (! $subscriptor->streamManager() instanceof GapDetection) {
            return false;
        }

        if (! $subscriptor->streamManager()->hasGap()) {
            return false;
        }

        return true;
    }
}
