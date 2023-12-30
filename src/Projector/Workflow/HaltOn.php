<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher;

class HaltOn
{
    private array $callbacks;

    /**
     * @param positive-int $cycle
     */
    public function cycleReach(int $cycle): self
    {
        $this->callbacks[StopWatcher::AT_CYCLE] = fn () => $cycle;

        return $this;
    }

    /**
     * @param positive-int $limit
     */
    public function masterCounterLimit(int $limit, bool $resetOnHalt = true): self
    {
        $this->callbacks[StopWatcher::MASTER_COUNTER_LIMIT] = fn () => [$limit, $resetOnHalt];

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
    public function expiredAt(int $timestamp): self
    {
        $this->callbacks[StopWatcher::EXPIRED_AT] = fn () => $timestamp;

        return $this;
    }

    public function callbacks(): array
    {
        return $this->callbacks;
    }
}
