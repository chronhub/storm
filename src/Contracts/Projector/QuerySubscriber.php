<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface QuerySubscriber extends Subscriber
{
    public function resets(): void;
}
