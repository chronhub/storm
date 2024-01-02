<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use DateTimeImmutable;

final readonly class CheckpointInserted
{
    public function __construct(
        public string $streamName,
        public int $streamPosition,
        public string|DateTimeImmutable $eventTime,
    ) {
    }

    public function __invoke(Subscriptor $subscriptor): Checkpoint
    {
        return $subscriptor->recognition()->insert($this->streamName, $this->streamPosition, $this->eventTime);
    }
}
