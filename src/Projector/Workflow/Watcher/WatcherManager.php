<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

class WatcherManager
{
    public function __construct(
        protected CycleWatcher $cycleWatcher,
        protected SprintWatcher $sprintWatcher,
        protected UserStateWatcher $userState,
        protected EventStreamWatcher $eventStreamWatcher,
        protected BatchCounterWatcher $batchCounterWatcher,
        protected AckedStreamWatcher $ackedStreamWatcher,
        protected BatchStreamWatcher $batchStreamWatcher,
        protected TimeWatcher $timeWatcher,
        protected StopWatcher $stopWatcher,
        protected MasterEventCounterWatcher $masterCounterWatcher,
        protected SnapshotWatcher $snapshotWatcher
    ) {
    }

    public function cycle(): CycleWatcher
    {
        return $this->cycleWatcher;
    }

    public function sprint(): SprintWatcher
    {
        return $this->sprintWatcher;
    }

    public function userState(): UserStateWatcher
    {
        return $this->userState;
    }

    public function batch(): BatchCounterWatcher
    {
        return $this->batchCounterWatcher;
    }

    public function masterCounter(): MasterEventCounterWatcher
    {
        return $this->masterCounterWatcher;
    }

    public function ackedStream(): AckedStreamWatcher
    {
        return $this->ackedStreamWatcher;
    }

    public function batchStream(): BatchStreamWatcher
    {
        return $this->batchStreamWatcher;
    }

    public function streamDiscovery(): EventStreamWatcher
    {
        return $this->eventStreamWatcher;
    }

    public function time(): TimeWatcher
    {
        return $this->timeWatcher;
    }

    public function stopWhen(): StopWatcher
    {
        return $this->stopWatcher;
    }

    public function snapshot(): SnapshotWatcher
    {
        return $this->snapshotWatcher;
    }
}
