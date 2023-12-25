<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Support\NoEventStreamCounter;

final readonly class PersistOrUpdate
{
    public function __construct(
        private PersistentManagement $management,
        private NoEventStreamCounter $noEventCounter,
    ) {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if (! $subscriptor->hasGap()) {
            $this->isEventReset($subscriptor)
                ? $this->management->update() : $this->management->store();
        }

        return $next($subscriptor);
    }

    private function isEventReset(Subscriptor $subscriptor): bool
    {
        match ($subscriptor->isEventReset()) {
            true => $this->noEventCounter->sleep(),
            default => $this->noEventCounter->reset(),
        };

        return $subscriptor->isEventReset();
    }
}
