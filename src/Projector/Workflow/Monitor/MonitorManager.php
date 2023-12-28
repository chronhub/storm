<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Monitor;

readonly class MonitorManager
{
    public function __construct(
        protected LoopMonitor $loopMonitor,
        protected SprintMonitor $sprintMonitor,
        protected StreamEventCounterMonitor $streamEventCounterMonitor,
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

    public function streamEventCounter(): StreamEventCounterMonitor
    {
        return $this->streamEventCounterMonitor;
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
