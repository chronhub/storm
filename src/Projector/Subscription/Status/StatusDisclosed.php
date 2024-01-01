<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Status;

use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\ProjectionStatus;

final readonly class StatusDisclosed
{
    public function __construct(
        public ProjectionStatus $oldStatus,
        public ProjectionStatus $newStatus
    ) {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setStatus($this->newStatus);
    }
}
