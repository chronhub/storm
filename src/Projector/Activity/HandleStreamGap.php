<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;

final readonly class HandleStreamGap
{
    public function __construct(private SubscriptionManagement $subscription)
    {
    }

    public function __invoke(PersistentSubscriber $subscriber, callable $next): callable|bool
    {
        // When a gap is detected and still retry left,
        // we sleep and store the projection if some event(s) has been handled
        if ($subscriber->streamBinder->hasGap()) {
            $subscriber->streamBinder->sleep();

            if (! $subscriber->eventCounter->isReset()) {
                $this->subscription->store();
            }
        }

        return $next($subscriber);
    }
}
