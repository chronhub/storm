<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Stream\GapType;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Subscription\Checkpoint\GapDetected;
use Chronhub\Storm\Projector\Subscription\Checkpoint\RecoverableGapDetected;
use Chronhub\Storm\Projector\Subscription\Checkpoint\UnrecoverableGapDetected;

class WhenCheckpointInserted
{
    public function __invoke(NotificationHub $hub, CheckpointInserted $event, Checkpoint $checkpoint): void
    {
        $listener = match ($checkpoint->type) {
            GapType::IN_GAP => GapDetected::class,
            GapType::RECOVERABLE_GAP => RecoverableGapDetected::class,
            GapType::UNRECOVERABLE_GAP => UnrecoverableGapDetected::class,
            default => null,
        };

        if ($listener !== null) {
            $hub->notify($listener, $event->streamName, $event->streamPosition);
        }
    }
}
