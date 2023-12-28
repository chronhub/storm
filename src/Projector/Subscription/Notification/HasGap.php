<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class HasGap
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->recognition()->hasGap();
    }
}
