<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Checkpoint;

use Chronhub\Storm\Projector\Stream\Checkpoint;

final readonly class SnapshotTaken
{
    public function __construct(public Checkpoint $checkpoint)
    {
    }
}
