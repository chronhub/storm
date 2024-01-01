<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Stream\Checkpoint;

final readonly class CheckpointAdded
{
    public function __construct(
        public string $streamName,
        public int $streamPosition
    ) {
    }

    public function __invoke(Subscriptor $subscriptor): Checkpoint
    {
        return $subscriptor->recognition()->insert($this->streamName, $this->streamPosition);
    }
}
