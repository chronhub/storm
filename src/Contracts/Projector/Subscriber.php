<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Subscription\Notification;

interface Subscriber
{
    public function start(ContextReader $context, bool $keepRunning): void;

    public function notify(): Notification;
}
