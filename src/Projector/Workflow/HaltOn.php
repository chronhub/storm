<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Projector\Stream\GapType;
use Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher;

class HaltOn
{
    protected array $callbacks;

    public function whenEmptyEventStream(?int $expiredAt = null): self
    {
        $this->callbacks[StopWatcher::EMPTY_EVENT_STREAM] = fn () => $expiredAt;

        return $this;
    }

    /**
     * @param positive-int $cycle
     */
    public function whenCycleReach(int $cycle): self
    {
        $this->callbacks[StopWatcher::CYCLE_REACH] = fn () => $cycle;

        return $this;
    }

    /**
     * @param positive-int $limit
     */
    public function whenStreamEventLimitReach(int $limit, bool $resetOnHalt = true): self
    {
        $this->callbacks[StopWatcher::COUNTER_REACH] = fn () => [$limit, $resetOnHalt];

        return $this;
    }

    public function whenGapDetected(GapType $gapType): self
    {
        $this->callbacks[StopWatcher::GAP_DETECTED] = fn () => $gapType;

        return $this;
    }

    /**
     * @param int<0,max> $timestamp
     */
    public function whenTimeExpired(int $timestamp): self
    {
        $this->callbacks[StopWatcher::TIME_EXPIRED] = fn () => $timestamp;

        return $this;
    }

    public function callbacks(): array
    {
        return $this->callbacks;
    }
}
