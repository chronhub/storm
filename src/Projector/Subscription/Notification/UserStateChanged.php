<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class UserStateChanged
{
    public function __construct(public array $userState)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->monitor()->userState()->put($this->userState);
    }
}