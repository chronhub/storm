<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Projector\ProjectionStatus;

final readonly class StatusChanged
{
    public function __construct(
        public ProjectionStatus $oldStatus,
        public ProjectionStatus $newStatus
    ) {
    }
}
