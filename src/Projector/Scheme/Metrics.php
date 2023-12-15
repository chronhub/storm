<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use function microtime;

final class Metrics
{
    private array $summary = [];

    private array $cycles = [];

    public readonly NoLoadedEventsMetrics $noLoadedEvent;

    public function __construct()
    {
        $this->noLoadedEvent = new NoLoadedEventsMetrics();
    }

    public function newCycle(): void
    {
        $this->cycles = $this->makeNewCycle();
    }

    public function increment(): void
    {
        $this->cycles['index']++;

    }

    public function end(): void
    {
        $this->cycles['count_no_loaded_events'] = $this->noLoadedEvent->count - 1;
        $this->cycles['ended_time'] = microtime(true);
    }

    // notify no streams loaded/ streams loaded /// ...

    public function addCountStreams(array $streamCounts): void
    {
        foreach ($streamCounts as $streamName => $count) {
            if (! isset($this->cycles['count_loaded_events_per_stream'][$streamName])) {
                $this->cycles['count_loaded_events_per_stream'][$streamName] = 0;
            }

            $this->cycles['count_loaded_events_per_stream'][$streamName] += $count;
        }
    }

    public function addGap(string $streamName, int $position): void
    {
        $this->cycles['gaps_per_stream'][$streamName][] = $position;
    }

    public function getCycles(): array
    {
        return $this->cycles;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }

    private function makeNewCycle(): array
    {
        return [
            'index' => 1,
            'started_time' => microtime(true),
            'ended_time' => 0,
            'count_loaded_events_per_stream' => [],
            'count_no_loaded_events' => 0,
            'gaps_per_stream' => [],
        ];
    }
}
