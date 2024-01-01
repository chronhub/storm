<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\EmptyListeners;

final class WhenFinalizeProjection
{
    public function __invoke(NotificationHub $hub, EmptyListeners $capture): void
    {
        if ($capture->shouldStop) {
            $hub->forgetAll();
        }
    }
}
