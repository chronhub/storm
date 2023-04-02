<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Scheme\GapDetector;
use Chronhub\Storm\Projector\Scheme\EventCounter;

interface PersistentSubscriptionInterface extends Subscription
{
    public function eventCounter(): EventCounter;

    public function gap(): GapDetector;
}
