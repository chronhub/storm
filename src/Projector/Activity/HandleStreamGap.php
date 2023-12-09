<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Projector\Subscription\Beacon;

final readonly class HandleStreamGap
{
    public function __construct(private PersistentSubscriber $subscription)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        // When a gap is detected and still retry left,
        // we sleep and store the projection if some event(s) has been handled
        if ($manager->streamBinder->hasGap()) {
            $manager->streamBinder->sleep();

            if (! $this->subscription->eventCounter()->isReset()) {
                $this->subscription->store();
            }
        }

        return $next($manager);
    }
}
