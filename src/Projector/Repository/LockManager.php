<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use DateTimeImmutable;

/**
 * @property $lockTimeoutMs The duration for which a lock is valid, in milliseconds
 * @property $lockThreshold The duration after which a lock should be refreshed, in milliseconds
 */
final class LockManager
{
    private ?DateTimeImmutable $lastLock = null;

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
     * Attempts to update the lock if it has exceeded the lock threshold.
     *
     * @return bool Whether the lock was updated.
     */
    public function tryUpdate(): bool
    {
        $now = $this->clock->now();

        if ($this->shouldUpdateLock($now)) {
            $this->lastLock = $now;

            return true;
        }

        return false;
    }

    /**
     * Refreshes the lock and returns the new lock value.
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
     * @param  DateTimeImmutable  $dateTime The new expiration time.
     * @return string The new lock value.
     */
    private function updateLockWithTimeout(DateTimeImmutable $dateTime): string
    {
        $newLockExpiration = $dateTime->modify('+'.$this->lockTimeoutMs.' milliseconds');

        $this->lastLock = $newLockExpiration;

        return $newLockExpiration->format($this->clock->getFormat());
    }

    /**
     * Determines whether the lock should be updated based on the current time and lock threshold.
     *
     * @param  DateTimeImmutable  $dateTime The current time.
     * @return bool Whether the lock should be updated.
     */
    private function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        if ($this->lastLock === null || $this->lockThreshold === 0) {
            return true;
        }

        $updateLockThreshold = $this->lastLock->modify('+'.$this->lockThreshold.' milliseconds');

        return $updateLockThreshold <= $dateTime;
    }
}
