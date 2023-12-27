<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

class GetStreamName
{
    public function __invoke(Subscriptor $subscriptor): string
    {
        return $subscriptor->getStreamName();
    }
}
