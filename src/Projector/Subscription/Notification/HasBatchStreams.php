<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final class HasBatchStreams
{
    public function __construct(public readonly bool $hasBatchStreams)
    {
    }
}
