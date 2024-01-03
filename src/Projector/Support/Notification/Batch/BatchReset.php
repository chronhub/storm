<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class BatchReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batch()->reset();
    }
}