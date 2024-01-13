<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\NotificationHub;

use function method_exists;

class WatcherManager
{
    protected array $watchers = [];

    public function __construct(
        CycleWatcher $cycleWatcher,
        SprintWatcher $sprintWatcher,
        UserStateWatcher $userState,
        EventStreamWatcher $eventStreamWatcher,
        BatchCounterWatcher $batchCounterWatcher,
        AckedStreamWatcher $ackedStreamWatcher,
        BatchStreamWatcher $batchStreamWatcher,
        TimeWatcher $timeWatcher,
        StopWatcher $stopWatcher,
        MasterEventCounterWatcher $masterCounterWatcher,
        SnapshotWatcher $snapshotWatcher
    ) {
        // checkMe till I do no know if watchers need contract
        $this->watchers = [
            'cycle' => $cycleWatcher,
            'sprint' => $sprintWatcher,
            'user_state' => $userState,
            'event_stream' => $eventStreamWatcher,
            'batch_counter' => $batchCounterWatcher,
            'acked_stream' => $ackedStreamWatcher,
            'batch_stream' => $batchStreamWatcher,
            'time' => $timeWatcher,
            'stop' => $stopWatcher,
            'master_counter' => $masterCounterWatcher,
            'snapshot' => $snapshotWatcher,
        ];
    }

    public function cycle(): CycleWatcher
    {
        return $this->watchers['cycle'];
    }

    public function sprint(): SprintWatcher
    {
        return $this->watchers['sprint'];
    }

    public function userState(): UserStateWatcher
    {
        return $this->watchers['user_state'];
    }

    public function batch(): BatchCounterWatcher
    {
        return $this->watchers['batch_counter'];
    }

    public function masterCounter(): MasterEventCounterWatcher
    {
        return $this->watchers['master_counter'];
    }

    public function ackedStream(): AckedStreamWatcher
    {
        return $this->watchers['acked_stream'];
    }

    public function batchStream(): BatchStreamWatcher
    {
        return $this->watchers['batch_stream'];
    }

    public function streamDiscovery(): EventStreamWatcher
    {
        return $this->watchers['event_stream'];
    }

    public function time(): TimeWatcher
    {
        return $this->watchers['time'];
    }

    public function snapshot(): SnapshotWatcher
    {
        return $this->watchers['snapshot'];
    }

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        foreach ($this->watchers as $watcher) {
            if (method_exists($watcher, 'subscribe')) {
                $watcher->subscribe($hub, $context);
            }
        }
    }
}
