<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Sprint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class SprintStopped
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->sprint()->halt();
    }
}
