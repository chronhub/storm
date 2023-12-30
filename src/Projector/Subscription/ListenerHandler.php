<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Action\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Action\WhenExpectSprintTermination;
use Chronhub\Storm\Projector\Subscription\Notification\IsSprintTerminated;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(NotificationHub $hub): void
    {
        $hub->addListeners([
            StreamIteratorSet::class => WhenBatchLoaded::class,
            IsSprintTerminated::class => WhenExpectSprintTermination::class,
        ]);
    }
}
