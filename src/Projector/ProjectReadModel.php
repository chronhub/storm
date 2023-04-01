<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\ReadModelCaster;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorCaster;
use Chronhub\Storm\Contracts\Projector\PersistentReadModelSubscription;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithContext;
    use InteractWithPersistentProjection;

    public function __construct(
        protected PersistentReadModelSubscription $subscription,
        protected ContextBuilder $context,
        protected ProjectionRepository $repository,
        protected Chronicler $chronicler,
        protected string $streamName,
        private ReadModel $readModel)
    {
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    protected function getCaster(): ReadModelProjectorCaster
    {
        return new ReadModelCaster(
            $this, $this->subscription->clock(), $this->subscription->currentStreamName
        );
    }
}
