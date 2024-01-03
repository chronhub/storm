<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleBegan;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleRenewed;
use Chronhub\Storm\Projector\Support\Notification\Handler\WhenBatchLoaded;
use Chronhub\Storm\Projector\Support\Notification\Handler\WhenCheckpointInserted;
use Chronhub\Storm\Projector\Support\Notification\Handler\WhenCycleBegin;
use Chronhub\Storm\Projector\Support\Notification\Handler\WhenCycleRenewed;
use Chronhub\Storm\Projector\Support\Notification\Handler\WhenEventStreamDiscovered;
use Chronhub\Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;
use Chronhub\Storm\Projector\Support\Notification\Stream\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            CycleBegan::class => WhenCycleBegin::class,
            CycleRenewed::class => WhenCycleRenewed::class,
            StreamIteratorSet::class => WhenBatchLoaded::class,
            CheckpointInserted::class => WhenCheckpointInserted::class,
            EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
        ]);
    }
}
