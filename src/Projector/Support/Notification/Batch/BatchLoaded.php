<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Batch;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class BatchLoaded
{
    public function __construct(public bool $hasBatchStreams)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batchStream()->hasLoadedStreams($this->hasBatchStreams);
    }
}
