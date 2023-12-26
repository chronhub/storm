<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final readonly class CheckpointUpdated
{
    public function __construct(public array $checkpoints)
    {
    }
}
