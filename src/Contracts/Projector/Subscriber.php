<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Subscriber
{
    public function start(ContextReader $context, bool $keepRunning): void;

    public function hub(): NotificationHub;
}
