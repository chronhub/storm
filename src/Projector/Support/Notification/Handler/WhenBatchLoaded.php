<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Batch\BatchLoaded;
use Chronhub\Storm\Projector\Support\Notification\Stream\StreamIteratorSet;

final class WhenBatchLoaded
{
    public function __invoke(NotificationHub $hub, StreamIteratorSet $event): void
    {
        $hub->expect(BatchLoaded::class, $event->iterator !== null);
    }
}
