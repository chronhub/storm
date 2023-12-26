<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\EmitterManagement;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Scope\EmitterAccess;

final readonly class EmitterSubscription implements EmitterSubscriber
{
    use InteractWithPersistentSubscription;

    public function __construct(
        protected Subscriptor $subscriptor,
        protected EmitterManagement $management,
        protected ActivityFactory $activities
    ) {
    }

    protected function getScope(): EmitterScope
    {
        return new EmitterAccess($this->management->notify(), $this->subscriptor->clock());
    }
}
