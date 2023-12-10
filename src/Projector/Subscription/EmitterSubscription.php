<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Projector\Scheme\EmitterProjectorScope;
use Chronhub\Storm\Stream\Stream;
use Closure;

final readonly class EmitterSubscription implements EmitterSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        public Subscription $subscription,
        public EmitterManagement $management,
    ) {
        // issue if rerun the projection,
        // emitted stream and stream cache keep his state,
        // which can interfere with some operation
    }

    public function getScope(): EmitterProjectorScopeInterface
    {
        $userScope = $this->subscription->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new EmitterProjectorScope($this->management);
    }
}
