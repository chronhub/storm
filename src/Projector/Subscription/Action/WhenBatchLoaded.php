<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Action;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchLoaded;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

final class WhenBatchLoaded
{
    public function __invoke(NotificationHub $hub, StreamIteratorSet $event): void
    {
        $hub->expect(BatchLoaded::class, $event->iterator !== null);
    }
}
