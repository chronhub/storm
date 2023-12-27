<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class HasBatchStreams
{
    public function __construct(public bool $hasBatchStreams)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->batchStreamsAware()->hasLoadedStreams($this->hasBatchStreams);
    }
}
