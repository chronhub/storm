<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsEventReset
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->monitor()->streamEventCounter()->isReset();
    }
}
