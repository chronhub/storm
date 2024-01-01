<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Handler\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Handler\WhenCycleChanged;
use Chronhub\Storm\Projector\Subscription\Handler\WhenEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\Checkpoint\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\Cycle\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\Stream\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            CycleChanged::class => WhenCycleChanged::class,
            StreamIteratorSet::class => WhenBatchLoaded::class,
            CheckpointAdded::class => WhenCheckpointAdded::class,
            EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
        ]);
    }
}
