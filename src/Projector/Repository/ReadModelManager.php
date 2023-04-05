<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;

final readonly class ReadModelManager implements ProjectionManagement
{
    public function __construct(
        private ReadModelSubscriptionInterface $subscription,
        protected ProjectionRepositoryInterface $repository,
        private ReadModel $readModel
    ) {
    }

    public function rise(): void
    {
        $this->subscription->sprint()->continue();

        if (! $this->repository->exists()) {
            $this->repository->create();
        }

        $this->repository->acquireLock();

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
        $this->repository->persist();

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->repository->reset();

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }
    }

    public function renew(): void
    {
        $this->repository->updateLock();
    }

    public function freed(): void
    {
        $this->repository->releaseLock();
    }

    public function boundState(): void
    {
        $this->repository->loadState();
    }

    public function close(): void
    {
        $this->repository->stop();
    }

    public function restart(): void
    {
        $this->repository->startAgain();
    }

    public function disclose(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function projectionName(): string
    {
        return $this->repository->projectionName();
    }
}
