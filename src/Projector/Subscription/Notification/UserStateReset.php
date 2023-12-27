<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

// todo
final readonly class UserStateReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->userState()->reset();
    }
}
