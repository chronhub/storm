<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateTimeImmutable;

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
     *
     * @return string The new lock value.
     */
    public function acquire(): string
    {
        $this->lastLock = $this->clock->now();

        return $this->increment();
    }

    /**
     * Attempts to set or update the lock if it has exceeded the lock threshold.
     *
     * @return bool Whether the lock was updated.
     */
    public function tryUpdate(): bool
    {
        $now = $this->clock->now();

        if ($this->shouldUpdateLockWithThreshold($now)) {
            $this->lastLock = $now;

            return true;
        }

        return false;
    }

    /**
     * Refreshes the lock with current time and returns the new lock value.
     *
     * @return string The new lock value.
     */
    public function refresh(): string
    {
        return $this->updateLockWithTimeout($this->clock->now());
    }

    /**
     * Increments the lock and returns the new lock value.
     *
     * @return string The new lock value.
     */
    public function increment(): string
    {
        return $this->updateLockWithTimeout($this->lastLock);
    }

    /**
     * Returns the current lock value.
     *
     * @return string The current lock value
     */
    public function current(): string
    {
        return $this->lastLock->format($this->clock->getFormat());
    }

    /**
     * Updates the lock with a new timeout and returns the new lock value.
     *
     * @param  DateTimeImmutable $dateTime The new expiration time.
     * @return string            The new lock value.
     */
    private function updateLockWithTimeout(DateTimeImmutable $dateTime): string
    {
        $this->lastLock = $dateTime->modify('+'.$this->lockTimeoutMs.' milliseconds');

        return $this->current();
    }

    private function shouldUpdateLockWithThreshold(DateTimeImmutable $currentTime): bool
    {
        if ($this->lastLock === null || $this->lockTimeoutMs === 0) {
            return true;
        }

        $incrementedLock = $this->lastLock->modify('+'.$this->lockThreshold.' milliseconds');

        return $this->clock->isGreaterThan($incrementedLock, $currentTime);
    }
}
