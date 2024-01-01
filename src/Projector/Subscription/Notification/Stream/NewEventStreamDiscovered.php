<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Stream;

final class NewEventStreamDiscovered
{
    public function __construct(public readonly string $eventStream)
    {
    }
}
