<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;

readonly class StopWhen
{
    public function __construct(private array $callbacks)
    {
    }

    public function __invoke(Subscription $subscription): bool
    {
        foreach ($this->callbacks as $callback) {
            if ($callback($this, $subscription) === true) {
                return true;
            }
        }

        return false;
    }

    public function counterIsReached(PersistentSubscriptionInterface $subscription, int $limit): bool
    {
        return $subscription->eventCounter()->count() === $limit;
    }

    public function gapIsDetected(PersistentSubscriptionInterface $subscription): bool
    {
        return $subscription->streamManager()->hasGap();
    }
}
