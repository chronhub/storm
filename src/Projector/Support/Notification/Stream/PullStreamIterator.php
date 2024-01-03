<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Iterator;

final class PullStreamIterator
{
    public function __invoke(Subscriptor $subscriptor): ?Iterator
    {
        return $subscriptor->pullStreamIterator();
    }
}
