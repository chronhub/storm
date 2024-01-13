<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Checkpoint;

final readonly class ShouldSnapshotCheckpoint
{
    public function __construct(public Checkpoint $checkpoint)
    {
    }
}
