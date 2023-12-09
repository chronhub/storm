<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;

final readonly class ResetEventCounter
{
    public function __invoke(PersistentSubscriber $subscriber, callable $next): callable|bool
    {
        $subscriber->eventCounter->reset();

        return $next($subscriber);
    }
}
