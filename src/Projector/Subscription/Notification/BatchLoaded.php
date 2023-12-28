<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class BatchLoaded
{
    public function __construct(public bool $hasBatchStreams)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->monitor()->batchStream()->hasLoadedStreams($this->hasBatchStreams);
    }
}
