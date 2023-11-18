<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;

final class ReadModelSubscription extends AbstractPersistentSubscription implements ReadModelSubscriptionInterface
{
    private ReadModel $readModel;

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->discoverStreams();
    }

    public function store(): void
    {
        parent::store();

        $this->readModel->persist();
    }

    public function revise(): void
    {
        parent::revise();

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete(); // todo propagate $withEmittedEvents as info for dispatcher

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->sprint()->stop();

        $this->resetProjection();
    }

    public function setReadModel(ReadModel $readModel): void
    {
        $this->readModel = $readModel;
    }
}
