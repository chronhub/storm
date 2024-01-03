<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use function explode;
use function is_array;
use function range;

trait ProvideOption
{
    protected readonly bool $signal;

    /**
     * @var positive-int
     */
    protected readonly int $cacheSize;

    /**
     * @var positive-int
     */
    protected readonly int $timeout;

    /**
     * @var int<0,max>
     */
    protected readonly int $lockout;

    /**
     * @var array<int|float,int|float>
     */
    protected readonly array $sleep;

    /**
     * @var positive-int
     */
    protected readonly int $blockSize;

    /**
     * @var array<int<0,max>>
     */
    protected readonly array $retries;

    /**
     * @var int<0,max>
     */
    protected readonly int $loadLimiter;

    protected readonly ?string $detectionWindows;

    protected readonly bool $onlyOnceDiscovery;

    /**
     * @var array{position: null|positive-int, time: null|positive-int, usleep: null|positive-int}
     */
    protected readonly array $snapshotInterval;

    public function getSignal(): bool
    {
        return $this->signal;
    }

    public function getCacheSize(): int
    {
        return $this->cacheSize;
    }

    public function getBlockSize(): int
    {
        return $this->blockSize;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getSleep(): array
    {
        return $this->sleep;
    }

    public function getLockout(): int
    {
        return $this->lockout;
    }

    public function getRetries(): array
    {
        return $this->retries;
    }

    public function getLoadLimiter(): int
    {
        return $this->loadLimiter;
    }

    public function getDetectionWindows(): ?string
    {
        return $this->detectionWindows;
    }

    public function getOnlyOnceDiscovery(): bool
    {
        return $this->onlyOnceDiscovery;
    }

    public function getSnapshotInterval(): array
    {
        return $this->snapshotInterval;
    }

    public function jsonSerialize(): array
    {
        return [
            self::SIGNAL => $this->getSignal(),
            self::CACHE_SIZE => $this->getCacheSize(),
            self::BLOCK_SIZE => $this->getBlockSize(),
            self::TIMEOUT => $this->getTimeout(),
            self::SLEEP => $this->getSleep(),
            self::LOCKOUT => $this->getLockout(),
            self::RETRIES => $this->getRetries(),
            self::DETECTION_WINDOWS => $this->getDetectionWindows(),
            self::LOAD_LIMITER => $this->getLoadLimiter(),
            self::ONLY_ONCE_DISCOVERY => $this->getOnlyOnceDiscovery(),
            self::SNAPSHOT_INTERVAL => $this->getSnapshotInterval(),
        ];
    }

    protected function setUpRetries(array|string $retries): void
    {
        if (is_array($retries)) {
            $this->retries = $retries;
        } else {
            [$start, $end, $step] = explode(',', $retries);

            $this->retries = range((int) $start, (int) $end, (int) $step);
        }
    }
}
