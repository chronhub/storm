<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\UserState;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class CurrentUserState
{
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->watcher()->userState()->get();
    }
}
