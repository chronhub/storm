<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsBatchCounterReached
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batchCounter()->isReached();
    }
}
