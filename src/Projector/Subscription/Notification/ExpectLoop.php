<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

// todo
final class ExpectLoop
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->monitor()->loop()->cycle();
    }
}
