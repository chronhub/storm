<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class HasBatch
{
    public function __construct(public bool $hasBatch)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->batch()->hasLoadedStreams($this->hasBatch);
    }
}
