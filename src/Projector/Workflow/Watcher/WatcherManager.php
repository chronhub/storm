<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\UserState;

readonly class WatcherManager
{
    public function __construct(
        protected LoopWatcher $loopMonitor,
        protected SprintMonitor $sprintMonitor,
        protected UserState $userState,
        protected EventCounterWatcher $eventCounterMonitor,
        protected AckedStreamWatcher $ackedStreamMonitor,
        protected BatchStreamWatcher $batchStreamMonitor
    ) {
    }

    public function loop(): LoopWatcher
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

    public function eventCounter(): EventCounterWatcher
    {
        return $this->eventCounterMonitor;
    }

    public function ackedStream(): AckedStreamWatcher
    {
        return $this->ackedStreamMonitor;
    }

    public function batchStream(): BatchStreamWatcher
    {
        return $this->batchStreamMonitor;
    }
}
