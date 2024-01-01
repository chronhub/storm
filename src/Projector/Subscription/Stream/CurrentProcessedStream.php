<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

class CurrentProcessedStream
{
    public function __invoke(Subscriptor $subscriptor): string
    {
        return $subscriptor->getProcessedStream();
    }
}
