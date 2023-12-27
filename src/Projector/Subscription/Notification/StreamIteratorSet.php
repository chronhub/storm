<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;

final readonly class StreamIteratorSet
{
    public function __construct(private MergeStreamIterator $iterator)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setStreamIterator($this->iterator);
    }
}
