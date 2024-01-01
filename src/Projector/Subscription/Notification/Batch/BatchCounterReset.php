<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class BatchCounterReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batchCounter()->reset();
    }
}
