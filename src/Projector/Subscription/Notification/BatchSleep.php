<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

use function count;

final class BatchSleep
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        if (count($subscriptor->ackedEvents()) > 0) {
            return;
        }

        $subscriptor->batch()->sleep();
    }
}
