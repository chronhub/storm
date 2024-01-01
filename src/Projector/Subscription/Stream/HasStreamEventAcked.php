<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class HasStreamEventAcked
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->ackedStream()->hasStreams();
    }
}
