<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Subscription\Listener\WhenBatchLoaded;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;

final class ListenerHandler
{
    public static function listen(HookHub $hub): void
    {
        $hub->addListener(StreamIteratorSet::class, WhenBatchLoaded::class);
    }
}
