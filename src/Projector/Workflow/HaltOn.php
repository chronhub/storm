<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher;

class HaltOn
{
    protected array $callbacks;

    /**
     * @param positive-int $cycle
     */
    public function cycleReach(int $cycle): self
    {
        $this->callbacks[StopWatcher::CYCLE_REACH] = fn () => $cycle;

        return $this;
    }

    /**
     * @param positive-int $limit
     */
    public function streamEventLimitReach(int $limit, bool $resetOnHalt = true): self
    {
        $this->callbacks[StopWatcher::COUNTER_REACH] = fn () => [$limit, $resetOnHalt];

        return $this;
    }

    public function gapDetected(): self
    {
        $this->callbacks[StopWatcher::GAP_DETECTED] = fn () => true;

        return $this;
    }

    /**
     * @param int<0,max> $timestamp
     */
    public function timeExpired(int $timestamp): self
    {
        $this->callbacks[StopWatcher::TIME_EXPIRED] = fn () => $timestamp;

        return $this;
    }

    public function callbacks(): array
    {
        return $this->callbacks;
    }
}
