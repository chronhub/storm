<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class StreamEventAckedReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->ackedStream()->reset();
    }
}
