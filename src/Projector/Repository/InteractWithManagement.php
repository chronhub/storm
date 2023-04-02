<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Projector\ProjectionStatus;

trait InteractWithManagement
{
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
