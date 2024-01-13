<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Projector\Checkpoint\GapType;
use Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher;

class HaltOn
{
    /**
     * @var array<string<StopWatcher::*>, callable>
     */
    protected array $callbacks;

    /**
     * Stop the projector when the event stream is empty after a given time is given
     * or, it will stop at the end of the first cycle.
     *
     * @return $this
     */
    public function whenEmptyEventStream(?int $expiredAt = null): self
    {
        $this->callbacks[StopWatcher::EMPTY_EVENT_STREAM] = fn () => $expiredAt;

        return $this;
    }

    /**
     * Stop the projector at the given cycle.
     *
     * @param  positive-int $cycle
     * @return $this
     */
    public function whenCycleReach(int $cycle): self
    {
        $this->callbacks[StopWatcher::CYCLE_REACH] = fn () => $cycle;

        return $this;
    }

    /**
     * Stop the projector when the given number of events acked or not is reached.
     * If resetOnHalt is true, the counter will be reset to 0 when the projector is halted,
     * which is useful when dev wants to restart the same projector instance.
     *
     * @param  positive-int $limit
     * @return $this
     */
    public function whenStreamEventLimitReach(int $limit, bool $resetOnHalt = true): self
    {
        $this->callbacks[StopWatcher::COUNTER_REACH] = fn () => [$limit, $resetOnHalt];

        return $this;
    }

    /**
     * Stop the projector when a gap is detected.
     *
     * @see GapType
     *
     * @return $this
     */
    public function whenGapDetected(GapType $gapType): self
    {
        $this->callbacks[StopWatcher::GAP_DETECTED] = fn () => $gapType;

        return $this;
    }

    /**
     * Stop the projector when the given time is reached.
     * As projector must stop gracefully, it could not stop at the exact time,
     * but it will stop at the next cycle.
     *
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
