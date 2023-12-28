<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class CheckpointAdded
{
    public function __construct(public string $streamName, public int $streamPosition)
    {
    }

    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->recognition()->insert($this->streamName, $this->streamPosition);
    }
}
