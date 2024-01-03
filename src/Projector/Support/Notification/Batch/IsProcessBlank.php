<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsProcessBlank
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->batch()->isReset() &&
            ! $subscriptor->watcher()->ackedStream()->hasStreams();
    }
}
