<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Scheme\CastReadModel;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelCasterInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithContext;
    use InteractWithPersistentProjection;

    public function __construct(
        protected ReadModelSubscriptionInterface $subscription,
        protected ContextInterface $context,
        protected ProjectionRepositoryInterface $repository,
        protected Chronicler $chronicler,
        protected string $streamName,
        private ReadModel $readModel
    ) {
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function getCaster(): ReadModelCasterInterface
    {
        return new CastReadModel(
            $this, $this->subscription->clock(), $this->subscription->currentStreamName
        );
    }
}
