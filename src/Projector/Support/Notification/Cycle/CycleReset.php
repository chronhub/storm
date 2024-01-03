<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Cycle;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CycleReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->cycle()->reset();
    }
}
