<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;

use function usleep;

final readonly class PersistOrUpdate
{
    public function __construct(private SubscriptionManagement $subscription)
    {
    }

    public function __invoke(PersistentSubscriber $subscriber, callable $next): callable|bool
    {
        if (! $subscriber->streamBinder->hasGap()) {
            // The event counter is reset when no event has been loaded,
            // and, when persistWhenThresholdReached was successfully called,
            // so, we sleep and try updating the lock or, we store the data
            if ($subscriber->eventCounter->isReset()) {
                usleep(microseconds: $subscriber->option->getSleep());

                $this->subscription->update();
            } else {
                $this->subscription->store();
            }
        }

        return $next($subscriber);
    }
}
