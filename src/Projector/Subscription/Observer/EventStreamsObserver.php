<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Observer;

use Chronhub\Storm\Contracts\Projector\Observer;
use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class EventStreamsObserver implements Observer
{
    public function update(Subscriptor $subscriptor): void
    {
        // TODO: Implement update() method.
    }
}
