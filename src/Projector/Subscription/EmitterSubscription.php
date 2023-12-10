<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Projector\Scheme\EmitterAccess;
use Closure;

final readonly class EmitterSubscription implements EmitterSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscription $subscription,
        protected EmittingManagement $management,
    ) {
    }

    public function getScope(): EmitterScope
    {
        $userScope = $this->subscription->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new EmitterAccess($this->management);
    }
}
