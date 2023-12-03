<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\ProjectionStatus;

trait RemoteStatusDiscovery
{
    private bool $isFirstCycle = true;

    protected function shouldStopOnDiscoveringStatus(PersistentSubscriptionInterface $subscription): bool
    {
        $shouldStop = $this->discovering($subscription);

        $this->isFirstCycle = false;

        return $shouldStop;
    }

    protected function discoverStatus(PersistentSubscriptionInterface $subscription): void
    {
        $this->discovering($subscription);
    }

    protected function isFirstCycle(): bool
    {
        return $this->isFirstCycle;
    }

    protected function disableFlag(): void
    {
        $this->isFirstCycle = false;
    }

    private function onStopping(PersistentSubscriptionInterface $subscription): bool
    {
        if ($this->isFirstCycle) {
            $subscription->synchronise();
        }

        $subscription->close();

        return $this->isFirstCycle;
    }

    private function onResetting(PersistentSubscriptionInterface $subscription): bool
    {
        $subscription->revise();

        if (! $this->isFirstCycle && $subscription->sprint()->inBackground()) {
            $subscription->restart();
        }

        return false;
    }

    private function onDeleting(PersistentSubscriptionInterface $subscription, bool $shouldDiscardEvents): bool
    {
        $subscription->discard($shouldDiscardEvents);

        return $this->isFirstCycle;
    }

    private function discovering(PersistentSubscriptionInterface $subscription): bool
    {
        return match ($subscription->disclose()->value) {
            ProjectionStatus::STOPPING->value => $this->onStopping($subscription),
            ProjectionStatus::RESETTING->value => $this->onResetting($subscription),
            ProjectionStatus::DELETING->value => $this->onDeleting($subscription, false),
            ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value => $this->onDeleting($subscription, true),
            default => false,
        };
    }
}
