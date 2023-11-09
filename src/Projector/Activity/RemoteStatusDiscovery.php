<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;

trait RemoteStatusDiscovery
{
    abstract public function isFirstExecution(): bool;

    protected function shouldStopOnDiscloseStatus(PersistentSubscriptionInterface $subscription): bool
    {
        return $this->discloseProjectionStatus($subscription);
    }

    protected function discloseProjectionStatus(PersistentSubscriptionInterface $subscription): bool
    {
        $statuses = $this->getStatuses($subscription);

        $statusFn = $statuses[$subscription->disclose()->value] ?? null;

        return $statusFn ? $statusFn() : false;
    }

    private function markAsStop(PersistentSubscriptionInterface $subscription): bool
    {
        if ($this->isFirstExecution()) {
            $subscription->refreshDetail();
        }

        $subscription->close();

        return $this->isFirstExecution();
    }

    private function markAsReset(PersistentSubscriptionInterface $subscription): bool
    {
        $subscription->revise();

        if (! $this->isFirstExecution() && $subscription->sprint()->inBackground()) {
            $subscription->restart();
        }

        return false;
    }

    private function markForDeletion(PersistentSubscriptionInterface $subscription, bool $shouldDiscardEvents): bool
    {
        $subscription->discard($shouldDiscardEvents);

        return $this->isFirstExecution();
    }

    private function getStatuses(PersistentSubscriptionInterface $subscription): array
    {
        return [
            ProjectionStatus::STOPPING->value => fn () => $this->markAsStop($subscription),
            ProjectionStatus::RESETTING->value => fn () => $this->markAsReset($subscription),
            ProjectionStatus::DELETING->value => fn () => $this->markForDeletion($subscription, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => fn () => $this->markForDeletion($subscription, true),
        ];
    }
}
