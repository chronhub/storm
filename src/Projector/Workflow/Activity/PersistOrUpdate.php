<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Subscription\Notification\ResetBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;

final readonly class PersistOrUpdate
{
    public function __construct(private PersistentManagement $management)
    {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if (! $subscriptor->hasGap()) {
            $this->isEventReset($subscriptor)
                ? $this->management->tryUpdateLock() : $this->management->store();
        }

        return $next($subscriptor);
    }

    private function isEventReset(Subscriptor $subscriptor): bool
    {
        match ($subscriptor->isEventReset()) {
            true => $subscriptor->notify(new SleepWhenEmptyBatchStreams()), //fixMe: acked event and batch event
            default => $subscriptor->notify(new ResetBatchStreams()),
        };

        return $subscriptor->isEventReset();
    }
}
