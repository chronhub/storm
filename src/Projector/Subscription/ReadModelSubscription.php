<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Scheme\ReadModelAccess;
use Closure;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscription $subscription,
        protected ReadingModelManagement $management,
    ) {
    }

    public function getScope(): ReadModelScope
    {
        $userScope = $this->subscription->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelAccess($this->management);
    }
}
