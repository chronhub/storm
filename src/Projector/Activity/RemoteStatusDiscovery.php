<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;

trait RemoteStatusDiscovery
{
    private bool $isFirstExecution = true;

    protected function shouldStopOnDiscoverStatus(PersistentSubscriptionInterface $subscription): bool
    {
        return $this->discover($subscription);
    }

    protected function discoverStatus(PersistentSubscriptionInterface $subscription): void
    {
        $this->discover($subscription);
    }

    protected function disableFlag(): void
    {
        $this->isFirstExecution = false;
    }

    protected function isFirstExecution(): bool
    {
        return $this->isFirstExecution;
    }

    private function discover(PersistentSubscriptionInterface $subscription): bool
    {
        $statuses = $this->getStatuses($subscription);

        $statusFn = $statuses[$subscription->disclose()->value] ?? null;

        return $statusFn ? $statusFn() : false;
    }

    private function onStopping(PersistentSubscriptionInterface $subscription): bool
    {
        if ($this->isFirstExecution) {
            $subscription->synchronise();
        }

        $subscription->close();

        return $this->isFirstExecution;
    }

    private function onResetting(PersistentSubscriptionInterface $subscription): bool
    {
        $subscription->revise();

        if (! $this->isFirstExecution && $subscription->sprint()->inBackground()) {
            $subscription->restart();
        }

        return false;
    }

    private function onDeleting(PersistentSubscriptionInterface $subscription, bool $shouldDiscardEvents): bool
    {
        $subscription->discard($shouldDiscardEvents);

        return $this->isFirstExecution;
    }

    private function getStatuses(PersistentSubscriptionInterface $subscription): array
    {
        return [
            ProjectionStatus::STOPPING->value => fn () => $this->onStopping($subscription),
            ProjectionStatus::RESETTING->value => fn () => $this->onResetting($subscription),
            ProjectionStatus::DELETING->value => fn () => $this->onDeleting($subscription, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => fn () => $this->onDeleting($subscription, true),
        ];
    }
}
