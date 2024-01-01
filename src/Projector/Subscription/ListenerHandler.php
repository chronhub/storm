<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CheckpointInserted;
use Chronhub\Storm\Projector\Subscription\Cycle\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Handler\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCycleChanged;
use Chronhub\Storm\Projector\Subscription\Handler\WhenEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Stream\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Stream\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            CycleChanged::class => WhenCycleChanged::class,
            StreamIteratorSet::class => WhenBatchLoaded::class,
            CheckpointInserted::class => WhenCheckpointAdded::class,
            EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
        ]);
    }
}
