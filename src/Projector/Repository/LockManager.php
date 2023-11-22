<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateTimeImmutable;

// todo test
class LockManager
{
    private ?DateTimeImmutable $lastLock = null;

    /**
     * @param int<0,max> $lockTimeoutMs The duration for which a lock is valid, in milliseconds
     * @param int<0,max> $lockThreshold The duration after which a lock should be refreshed, in milliseconds
     */
    public function __construct(
        private readonly SystemClock $clock,
        private readonly int $lockTimeoutMs,
        private readonly int $lockThreshold
    ) {
    }

    /**
     * Acquires a lock and returns the new lock value.
     */
    public function acquire(): string
    {
        return $this->updateLockWithTimeout($this->clock->now());
    }

    /**
     * Refreshes the lock with current time and returns the new lock value.
     */
    public function refresh(DateTimeImmutable $currentTime): string
    {
        return $this->updateLockWithTimeout($currentTime);
    }

    /**
     * Determines whether the lock should be refreshed
     * based on the current time and lock settings.
     */
    public function shouldRefresh(DateTimeImmutable $currentTime): bool
    {
        // fixMe test scenarios when lastLock is null
        if ($this->lastLock === null || $this->lockTimeoutMs === 0) {
            return true;
        }

        $remainingTime = $this->lastLock->getTimestamp() - $currentTime->getTimestamp();

        return $remainingTime < $this->lockThreshold;
    }

    /**
     * Returns the current lock value.
     */
    public function current(): string
    {
        return $this->lastLock->format($this->clock->getFormat());
    }

    /**
     * Updates the lock with a new timeout and returns the new lock value.
     *
     * @param DateTimeImmutable $dateTime The new expiration time.
     */
    private function updateLockWithTimeout(DateTimeImmutable $dateTime): string
    {
        $this->lastLock = $dateTime->modify('+'.$this->lockTimeoutMs.' milliseconds');

        return $this->current();
    }
}
