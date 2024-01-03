<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsBatchReset
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batch()->isReset();
    }
}
