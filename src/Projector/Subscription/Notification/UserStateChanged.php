<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

final readonly class UserStateChanged
{
    public function __construct(public array $userState)
    {
    }
}
