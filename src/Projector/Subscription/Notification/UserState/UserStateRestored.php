<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\UserState;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class UserStateRestored
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->restoreUserState();
    }
}
