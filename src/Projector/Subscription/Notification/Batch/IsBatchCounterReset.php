<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsBatchCounterReset
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batchCounter()->isReset();
    }
}
