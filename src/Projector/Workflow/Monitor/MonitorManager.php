<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Monitor;

use Chronhub\Storm\Contracts\Projector\UserState;

readonly class MonitorManager
{
    public function __construct(
        protected LoopMonitor $loopMonitor,
        protected SprintMonitor $sprintMonitor,
        protected UserState $userState,
        protected EventCounterMonitor $eventCounterMonitor,
        protected AckedStreamMonitor $ackedStreamMonitor,
        protected BatchStreamMonitor $batchStreamMonitor
    ) {
    }

    public function loop(): LoopMonitor
    {
        return $this->loopMonitor;
    }

    public function sprint(): SprintMonitor
    {
        return $this->sprintMonitor;
    }

    public function userState(): UserState
    {
        return $this->userState;
    }

    public function eventCounter(): EventCounterMonitor
    {
        return $this->eventCounterMonitor;
    }

    public function ackedStream(): AckedStreamMonitor
    {
        return $this->ackedStreamMonitor;
    }

    public function batchStream(): BatchStreamMonitor
    {
        return $this->batchStreamMonitor;
    }
}
