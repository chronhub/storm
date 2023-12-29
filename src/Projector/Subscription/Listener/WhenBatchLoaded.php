<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Listener;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchLoaded;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

final class WhenBatchLoaded
{
    public function __invoke(HookHub $hub, StreamIteratorSet $event): void
    {
        $hub->expect(BatchLoaded::class, $event->iterator !== null);
    }
}
