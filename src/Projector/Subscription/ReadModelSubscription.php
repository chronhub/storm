<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Scope\ReadModelAccess;
use Closure;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscription $subscription,
        protected ReadModelManagement $management,
    ) {
    }

    public function reset(): void
    {
        $this->management->revise();
    }

    public function delete(bool $withReadModel): void
    {
        $this->management->discard($withReadModel);
    }

    public function getScope(): ReadModelScope
    {
        $userScope = $this->subscription->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelAccess();
    }
}
