<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithContext;
    use InteractWithPersistentProjection;

    public function __construct(
        protected ReadModelSubscriptionInterface $subscription,
        protected ContextReaderInterface $context,
        protected string $streamName,
        private ReadModel $readModel
    ) {
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function getScope(): ReadModelProjectorScopeInterface
    {
        return new ReadModelProjectorScope(
            $this, $this->subscription->clock(), fn (): ?string => $this->subscription->currentStreamName()
        );
    }
}
