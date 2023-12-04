<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;
use Closure;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithContext;
    use InteractWithPersistentProjection;

    public function __construct(
        protected ReadModelSubscriptionInterface $subscription,
        private ReadModel $readModel
    ) {
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function getScope(): ReadModelProjectorScopeInterface
    {
        $userScope = $this->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelProjectorScope(
            $this, $this->subscription->clock(), fn (): string => $this->subscription->currentStreamName()
        );
    }
}
