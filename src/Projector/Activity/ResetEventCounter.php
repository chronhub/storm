<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Subscription\Beacon;

final readonly class ResetEventCounter
{
    public function __construct(private EventCounter $eventCounter)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        $this->eventCounter->reset();

        return $next($manager);
    }
}
