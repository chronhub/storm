<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final readonly class EmptyListeners
{
    public function __construct(public bool $shouldStop)
    {
    }
}
