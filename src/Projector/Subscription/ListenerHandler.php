<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Action\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Action\WhenCheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Action\WhenCycleChanged;
use Chronhub\Storm\Projector\Subscription\Action\WhenEventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\EventStreamDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

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
