<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final class ExpectUserState
{
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->monitor()->userState()->get();
    }
}
