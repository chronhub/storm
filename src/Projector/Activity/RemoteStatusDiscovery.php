<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Projector\ProjectionStatus;

trait RemoteStatusDiscovery
{
    protected ?ProjectionManagement $repository;

    protected function recoverProjectionStatus(bool $isFirstExecution, bool $shouldKeepRunning): bool
    {
        $statuses = $this->getStatuses($isFirstExecution, $shouldKeepRunning);

        $statusFn = $statuses[$this->repository->disclose()->value] ?? null;

        return $statusFn ? $statusFn() : false;
    }

    private function markAsStop(bool $isFirstExecution): bool
    {
        if ($isFirstExecution) {
            $this->repository->boundState();
        }

        $this->repository->close();

        return $isFirstExecution;
    }

    private function markAsReset(bool $isFirstExecution, bool $shouldRestart): bool
    {
        $this->repository->revise();

        if (! $isFirstExecution && $shouldRestart) {
            $this->repository->restart();
        }

        return false;
    }

    private function markForDeletion(bool $isFirstExecution, bool $shouldDiscardEvents): bool
    {
        $this->repository->discard($shouldDiscardEvents);

        return $isFirstExecution;
    }

    private function getStatuses(bool $isFirstExecution, bool $shouldKeepRunning): array
    {
        return [
            ProjectionStatus::STOPPING->value => fn () => $this->markAsStop($isFirstExecution),
            ProjectionStatus::RESETTING->value => fn () => $this->markAsReset($isFirstExecution, $shouldKeepRunning),
            ProjectionStatus::DELETING->value => fn () => $this->markForDeletion($isFirstExecution, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => fn () => $this->markForDeletion($isFirstExecution, true),
        ];
    }
}
