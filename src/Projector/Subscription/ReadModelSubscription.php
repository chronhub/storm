<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Scope\ReadModelAccess;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscriptor $subscriptor,
        protected ReadModelManagement $management,
        protected ActivityFactory $activities
    ) {
    }

    protected function getScope(): ReadModelScope
    {
        return new ReadModelAccess(
            $this->management->notify(),
            $this->management->getReadModel(),
            $this->subscriptor->clock()
        );
    }
}
