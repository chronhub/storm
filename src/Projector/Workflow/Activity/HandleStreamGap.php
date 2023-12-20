<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Subscription\Subscription;

final readonly class HandleStreamGap
{
    public function __construct(private PersistentManagement $management)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        // When a gap is detected and still retry left,
        // we sleep and store the projection if some event(s) has been loaded and incremented

        if ($subscription->hasGapDetection() && $subscription->streamManager->hasGap()) {
            $subscription->streamManager->sleepWhenGap();

            if (! $subscription->eventCounter->isReset()) {
                $this->management->store();
            }
        }

        return $next($subscription);
    }
}
