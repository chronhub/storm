<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Checkpoint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class CheckpointUpdated
{
    public function __construct(public array $checkpoints)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->recognition()->update($this->checkpoints);
    }
}
