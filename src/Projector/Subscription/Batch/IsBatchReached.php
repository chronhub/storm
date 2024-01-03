<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsBatchReached
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batch()->isReached();
    }
}
