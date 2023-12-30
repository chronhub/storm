<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Action\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Action\WhenExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Action\WhenGapDetected;
use Chronhub\Storm\Projector\Subscription\Action\WhenLoopRenew;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Notification\GapDetected;
use Chronhub\Storm\Projector\Subscription\Notification\LoopRenew;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            StreamIteratorSet::class => WhenBatchLoaded::class,
            GapDetected::class => WhenGapDetected::class,
            LoopRenew::class => WhenLoopRenew::class,
            ExpectSprintTermination::class => WhenExpectSprintTermination::class,
        ]);
    }
}
