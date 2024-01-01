<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\MasterCounter;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CurrentMasterCount
{
    public function __invoke(Subscriptor $subscriptor): int
    {
        return $subscriptor->watcher()->masterCounter()->current();
    }
}
