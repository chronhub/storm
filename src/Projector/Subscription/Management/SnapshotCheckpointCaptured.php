<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Management;

use Chronhub\Storm\Projector\Stream\Checkpoint;

final readonly class SnapshotCheckpointCaptured
{
    public function __construct(public Checkpoint $checkpoint)
    {
    }
}
