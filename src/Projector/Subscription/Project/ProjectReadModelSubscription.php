<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Project;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\InteractWithContext;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\ReadModelCaster;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorCaster;

final readonly class ProjectReadModelSubscription implements ReadModelProjector
{
    use InteractWithContext;
    use ProvidePersistentSubscription;

    public function __construct(
        protected Subscription $subscription,
        protected ContextBuilder $context,
        protected SubscriptionManagement $repository,
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
        return new ReadModelCaster($this, $this->subscription->clock, $this->subscription->currentStreamName);
    }
}
