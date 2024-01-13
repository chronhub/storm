<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Checkpoint;

use Chronhub\Storm\Projector\Checkpoint\Checkpoint;

final readonly class SnapshotTaken
{
    public function __construct(public Checkpoint $checkpoint)
    {
    }
}
