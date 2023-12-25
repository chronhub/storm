<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final class StreamEventAcked
{
    public function __construct(public readonly string $eventClass)
    {
    }
}
