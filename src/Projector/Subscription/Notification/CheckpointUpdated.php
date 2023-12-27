<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class CheckpointUpdated
{
    public function __construct(public array $checkpoints)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->streamManager()->update($this->checkpoints);
    }
}
