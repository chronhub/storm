<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

class StopWatcher
{
    const GAP_DETECTED = 'gapDetected';

    const AT_CYCLE = 'atCycle';

    const MASTER_COUNTER_LIMIT = 'masterCounterLimit';

    const EXPIRED_AT = 'expiredAt';

    /**
     * @var array<string, callable>
     */
    protected array $stopWhen = [];

    public function setCallbacks(array $callbacks): void
    {
        $this->stopWhen = $callbacks;
    }

    public function gapDetected(): bool
    {
        return $this->haltOn(self::GAP_DETECTED, false) ?? false;
    }

    public function masterCounterReach(int $currentCount): bool
    {
        return $this->haltOn(self::MASTER_COUNTER_LIMIT, -1) === $currentCount;
    }

    public function atCycle(int $currentCycle): bool
    {
        return $this->haltOn(self::AT_CYCLE, -1) === $currentCycle;
    }

    public function expiredAt(int|float $currentTime): bool
    {
        return $this->haltOn(self::EXPIRED_AT, PHP_INT_MAX) <= $currentTime;
    }

    private function haltOn(string $name, mixed $default): mixed
    {
        if (isset($this->stopWhen[$name])) {
            return value($this->stopWhen[$name]);
        }

        return $default;
    }
}
