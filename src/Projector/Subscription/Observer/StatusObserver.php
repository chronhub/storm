<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Observer;

use Chronhub\Storm\Contracts\Projector\Observer;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\ProjectionStatus;

final readonly class StatusObserver implements Observer
{
    public function __construct(private ProjectionStatus $status)
    {
    }

    public function update(Subscriptor $subscriptor): void
    {

    }
}
