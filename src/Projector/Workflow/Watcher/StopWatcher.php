<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

class StopWatcher
{
    const GAP_DETECTED = 'gapDetected';

    const AT_CYCLE = 'atCycle';

    const BATCH_COUNTER_LIMIT = 'batchCounterLimit';

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
        return $this->findCallback(self::GAP_DETECTED) ?? false;
    }

    public function atCycle(int $currentCycle): bool
    {
        return $this->findCallback(self::AT_CYCLE) === $currentCycle;
    }

    public function batchCounterReach(int $currentCount): bool
    {
        return $this->findCallback(self::BATCH_COUNTER_LIMIT) === $currentCount;
    }

    public function expiredAt(int|float $currentTime): bool
    {
        return $this->findCallback(self::EXPIRED_AT) === $currentTime;
    }

    private function findCallback(string $name): mixed
    {
        if (isset($this->stopWhen[$name])) {
            return value($this->stopWhen[$name]);
        }

        return null;
    }
}
