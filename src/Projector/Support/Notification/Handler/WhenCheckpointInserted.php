<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Handler;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Checkpoint\Checkpoint;
use Chronhub\Storm\Projector\Checkpoint\GapType;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\GapDetected;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\RecoverableGapDetected;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\UnrecoverableGapDetected;

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
