<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\UserState;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class IsUserStateInitialized
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->isUserStateInitialized();
    }
}
