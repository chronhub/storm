<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ProjectionManagerInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;

final readonly class ReadModelRepository implements ProjectionRepositoryInterface
{
    public function __construct(private ReadModelSubscriptionInterface $subscription,
                                private ProjectionManagerInterface $manager,
                                private ReadModel $readModel)
    {
    }

    public function rise(): void
    {
        $this->subscription->sprint()->continue();

        if (! $this->manager->exists()) {
            $this->manager->create();
        }

        $this->manager->acquireLock();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->subscription->streamPosition()->watch(
            $this->subscription->context()->queries()
        );

        $this->boundState();
    }

    public function store(): void
    {
        $this->manager->persist();

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->manager->reset();

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->manager->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }
    }

    public function renew(): void
    {
        $this->manager->updateLock();
    }

    public function freed(): void
    {
        $this->manager->releaseLock();
    }

    public function boundState(): void
    {
        $this->manager->loadState();
    }

    public function close(): void
    {
        $this->manager->stop();
    }

    public function restart(): void
    {
        $this->manager->startAgain();
    }

    public function disclose(): ProjectionStatus
    {
        return $this->manager->loadStatus();
    }

    public function projectionName(): string
    {
        return $this->manager->projectionName();
    }
}
