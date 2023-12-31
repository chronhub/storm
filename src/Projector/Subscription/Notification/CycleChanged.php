<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final class CycleChanged
{
    public function __construct(public bool $sprintTerminated)
    {
    }
}
