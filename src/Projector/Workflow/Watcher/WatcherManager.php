<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\UserState;

readonly class WatcherManager
{
    public function __construct(
        protected LoopWatcher $loopWatcher,
        protected SprintWatcher $sprintWatcher,
        protected UserState $userState,
        protected BatchCounterWatcher $batchCounterWatcher,
        protected AckedStreamWatcher $ackedStreamWatcher,
        protected BatchStreamWatcher $batchStreamWatcher,
        protected TimeWatcher $timeWatcher,
        protected StopWatcher $stopWatcher,
        protected MasterEventCounterWatcher $masterCounterWatcher
    ) {
    }

    public function loop(): LoopWatcher
    {
        return $this->loopWatcher;
    }

    public function sprint(): SprintWatcher
    {
        return $this->sprintWatcher;
    }

    public function userState(): UserState
    {
        return $this->userState;
    }

    public function batchCounter(): BatchCounterWatcher
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

    public function time(): TimeWatcher
    {
        return $this->timeWatcher;
    }

    public function stopWhen(): StopWatcher
    {
        return $this->stopWatcher;
    }
}
