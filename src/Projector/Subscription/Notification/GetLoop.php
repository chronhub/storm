<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

// todo
final class GetLoop
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->loop()->cycle();
    }
}
