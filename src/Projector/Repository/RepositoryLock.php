<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use DateInterval;
use DateTimeImmutable;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function floor;
use function sprintf;

class RepositoryLock
{
    private ?DateTimeImmutable $lastLock = null;

    public function __construct(private readonly SystemClock $clock,
                                private readonly int $lockTimeoutMs,
                                private readonly int $lockThreshold)
    {
    }

    public function acquire(): string
    {
        $this->lastLock = $this->clock->now();

        return $this->increment();
    }

    public function tryUpdate(): bool
    {
        $now = $this->clock->now();

        if ($this->shouldUpdateLock($now)) {
            $this->lastLock = $now;

            return true;
        }

        return false;
    }

    public function refresh(): string
    {
        return $this->updateLockWithTimeout($this->clock->now());
    }

    public function increment(): string
    {
        return $this->updateLockWithTimeout($this->lastLock);
    }

    public function current(): ?string
    {
        return $this->lastLock?->format($this->clock->getFormat());
    }

    protected function updateLockWithTimeout(DateTimeImmutable $dateTime): string
    {
        return $dateTime
            ->modify('+'.$this->lockTimeoutMs.' milliseconds')
            ->format($this->clock->getFormat());
    }

    protected function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        if ($this->lastLock === null || $this->lockThreshold === 0) {
            return true;
        }

        return $this->incrementLockWithThreshold() <= $dateTime;
    }

    protected function incrementLockWithThreshold(): DateTimeImmutable
    {
        $interval = sprintf('PT%sS', floor($this->lockThreshold / 1000));

        $updateLockThreshold = new DateInterval($interval);

        $updateLockThreshold->f = ($this->lockThreshold % 1000) / 1000;

        return $this->lastLock->add($updateLockThreshold);
    }
}
