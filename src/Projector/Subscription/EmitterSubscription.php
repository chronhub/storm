<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

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
        protected Notification $notification
    ) {
    }

    public function reset(): void
    {
        $this->management->revise();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->management->discard($withEmittedEvents);
    }

    protected function getScope(): EmitterScope
    {
        return new EmitterAccess($this->management, $this->subscriptor->clock());
    }
}
