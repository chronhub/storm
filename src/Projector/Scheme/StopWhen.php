<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\StateManagement;

readonly class StopWhen
{
    public function __construct(private array $callbacks)
    {
    }

    public function __invoke(StateManagement $subscription): bool
    {
        foreach ($this->callbacks as $callback) {
            if ($callback($this, $subscription) === true) {
                return true;
            }
        }

        return false;
    }

    public function counterIsReached(PersistentSubscriber $subscription, int $limit): bool
    {
        return $subscription->eventCounter()->count() === $limit;
    }

    public function gapIsDetected(PersistentSubscriber $subscription): bool
    {
        return $subscription->streamManager()->hasGap();
    }
}
