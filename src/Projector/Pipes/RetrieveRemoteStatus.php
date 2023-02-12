<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

trait RetrieveRemoteStatus
{
    public function __construct(protected readonly ProjectorRepository $repository)
    {
    }

    protected function stopOnLoadingRemoteStatus(bool $keepRunning): bool
    {
        return $this->discoverRemoteProjectionStatus(true, $keepRunning);
    }

    protected function reloadRemoteStatus(bool $keepRunning): void
    {
        $this->discoverRemoteProjectionStatus(false, $keepRunning);
    }

    private function discoverRemoteProjectionStatus(bool $firstExecution, bool $keepRunning): bool
    {
        return match ($this->repository->disclose()) {
            ProjectionStatus::STOPPING => $this->markAsStop($firstExecution),
            ProjectionStatus::RESETTING => $this->markAsReset($firstExecution, $keepRunning),
            ProjectionStatus::DELETING => $this->markAsDelete($firstExecution, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS => $this->markAsDelete($firstExecution, true),
            default => false
        };
    }

    private function markAsStop(bool $firstExecution): bool
    {
        if ($firstExecution) {
            $this->repository->boundState();
        }

        $this->repository->close();

        return $firstExecution;
    }

    private function markAsReset(bool $firstExecution, bool $keepRunning): bool
    {
        $this->repository->revise();

        if (! $firstExecution && $keepRunning) {
            $this->repository->restart();
        }

        return false;
    }

    private function markAsDelete(bool $firstExecution, bool $withEmittedEvents): bool
    {
        $this->repository->discard($withEmittedEvents);

        return $firstExecution;
    }
}
