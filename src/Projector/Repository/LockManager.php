<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use DateTimeImmutable;

class LockManager
{
    private ?DateTimeImmutable $lastLock = null;

    /**
     * @param positive-int $lockTimeout   The duration for which a lock is valid, in milliseconds
     * @param int<0,max>   $lockThreshold The duration after which a lock should be refreshed, in milliseconds
     */
    public function __construct(
        protected readonly SystemClock $clock,
        public readonly int $lockTimeout,
        public readonly int $lockThreshold
    ) {
    }

    /**
     * Acquires a lock and returns the new lock value.
     */
    public function acquire(): string
    {
        $this->lastLock = $this->clock->now();

        return $this->current();
    }

    /**
     * Refreshes the lock returns the new lock value.
     */
    public function refresh(): string
    {
        $this->lastLock = $this->clock->now()->modify('+'.$this->lockTimeout.' milliseconds');

        return $this->current();
    }

    /**
     * Determines whether the lock should be refreshed.
     */
    public function shouldRefresh(): bool
    {
        if ($this->lastLock === null || $this->lockThreshold === 0) {
            return true;
        }

        $adjustedLock = $this->lastLock->modify('+'.$this->lockThreshold.' milliseconds');

        return $adjustedLock < $this->clock->now();
    }

    /**
     * Returns the current lock value.
     *
     * @throws RuntimeException When lock is not acquired
     */
    public function current(): string
    {
        if ($this->lastLock === null) {
            throw new RuntimeException('Lock is not acquired');
        }

        return $this->lastLock->format($this->clock->getFormat());
    }
}
