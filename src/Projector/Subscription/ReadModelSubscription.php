<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;
use Closure;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        public Subscription $subscription,
        public ReadModelManagement $management,
    ) {
    }

    public function readModel(): ReadModel
    {
        return $this->subscription->readModel;
    }

    public function getScope(): ReadModelProjectorScopeInterface
    {
        $userScope = $this->subscription->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelProjectorScope($this->management, $this);
    }
}
