<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleBegan;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleRenewed;
use Chronhub\Storm\Projector\Subscription\Handler\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCheckpointInserted;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCycleBegin;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCycleRenewed;
use Chronhub\Storm\Projector\Subscription\Handler\WhenEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Stream\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Stream\StreamIteratorSet;

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
