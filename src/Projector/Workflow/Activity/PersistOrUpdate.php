<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class PersistOrUpdate
{
    public function __construct(private PersistentManagement $management)
    {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if (! $subscriptor->hasGap()) {
            $subscriptor->isEventReset()
                ? $this->management->tryUpdateLock()
                : $this->management->store();
        }

        return $next($subscriptor);
    }
}
