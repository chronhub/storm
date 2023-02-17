<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use DateInterval;
use DateTimeImmutable;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use function floor;
use function substr;
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

        return $this->currentLock();
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
        return $this->createLockWithMillisecond($this->clock->now());
    }

    public function currentLock(): string
    {
        return $this->createLockWithMillisecond($this->lastLock);
    }

    public function lastLockUpdate(): ?string
    {
        return $this->lastLock?->format($this->clock->getFormat());
    }

    protected function createLockWithMillisecond(DateTimeImmutable $dateTime): string
    {
        $microSeconds = (string) ((int) $dateTime->format('u') + ($this->lockTimeoutMs * 1000));

        $seconds = substr($microSeconds, 0, -6);

        if ($seconds === '') {
            $seconds = 0;
        }

        return $dateTime
            ->modify('+'.$seconds.' seconds')
            ->format('Y-m-d\TH:i:s').'.'.substr($microSeconds, -6);
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
