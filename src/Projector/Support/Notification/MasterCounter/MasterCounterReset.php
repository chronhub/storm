<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\MasterCounter;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class MasterCounterReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->masterCounter()->reset();
    }
}
