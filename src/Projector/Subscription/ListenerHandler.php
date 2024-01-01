<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Action\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Action\WhenCheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Action\WhenCycleChanged;
use Chronhub\Storm\Projector\Subscription\Action\WhenExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Action\WhenFinalizeProjection;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\EmptyListeners;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            StreamIteratorSet::class => WhenBatchLoaded::class,
            CheckpointAdded::class => WhenCheckpointAdded::class,
            IsSprintTerminated::class => WhenExpectSprintTermination::class,
            CycleChanged::class => WhenCycleChanged::class,
            EmptyListeners::class => WhenFinalizeProjection::class,
        ]);
    }
}
