<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class RisePersistentProjection
{
    public function __construct(
        private MonitorRemoteStatus $monitor,
        private PersistentManagement $management
    ) {
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        if ($subscriptor->isFirstLoop()) {
            // depending on the discovered status, the projection can be stopped early,
            // on stopping and on deleting.
            if ($this->monitor->shouldStop($this->management, $subscriptor->inBackground())) {
                return false;
            }

            $this->management->rise();
        }

        return $next($subscriptor);
    }
}