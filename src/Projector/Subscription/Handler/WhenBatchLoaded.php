<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\Batch\BatchLoaded;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\StreamIteratorSet;

final class WhenBatchLoaded
{
    public function __invoke(NotificationHub $hub, StreamIteratorSet $event): void
    {
        $hub->expect(BatchLoaded::class, $event->iterator !== null);
    }
}
