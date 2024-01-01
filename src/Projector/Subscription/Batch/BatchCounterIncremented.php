<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class BatchCounterIncremented
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batchCounter()->increment();

        $subscriptor->watcher()->masterCounter()->increment();
    }
}
