<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class EventCounterReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->monitor()->streamEventCounter()->reset();
    }
}
