<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

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
        return new ReadModelAccess($this->management);
    }
}
