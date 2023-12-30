<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class KeepMasterCounterOnStop
{
    public function __construct(public bool $keepMasterLimitOnStop = true)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->masterCounter()->doNotReset($this->keepMasterLimitOnStop);
    }
}
