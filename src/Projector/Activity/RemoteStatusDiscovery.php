<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;

trait RemoteStatusDiscovery
{
    protected ?PersistentSubscriptionInterface $subscription;

    protected function discloseProjectionStatus(bool $isFirstExecution, bool $shouldKeepRunning): bool
    {
        $statuses = $this->getStatuses($isFirstExecution, $shouldKeepRunning);

        $statusFn = $statuses[$this->subscription->disclose()->value] ?? null;

        return $statusFn ? $statusFn() : false;
    }

    private function markAsStop(bool $isFirstExecution): bool
    {
        if ($isFirstExecution) {
            $this->subscription->boundState();
        }

        $this->subscription->close();

        return $isFirstExecution;
    }

    private function markAsReset(bool $isFirstExecution, bool $shouldRestart): bool
    {
        $this->subscription->revise();

        if (! $isFirstExecution && $shouldRestart) {
            $this->subscription->restart();
        }

        return false;
    }

    private function markForDeletion(bool $isFirstExecution, bool $shouldDiscardEvents): bool
    {
        $this->subscription->discard($shouldDiscardEvents);

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
