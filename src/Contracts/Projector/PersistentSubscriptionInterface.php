<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;

interface PersistentSubscriptionInterface extends Subscription
{
    public function eventCounter(): EventCounter;

    public function gap(): StreamGapDetector;
}
