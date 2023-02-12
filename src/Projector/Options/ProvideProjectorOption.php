<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use function range;
use function explode;
use function is_array;

trait ProvideProjectorOption
{
    /**
     * Dispatch pcntl async signal
     *
     * @var bool
     */
    public readonly bool $dispatchSignal;

    /**
     * Number of stream to keep in cache
     *
     * @var int
     */
    public readonly int $streamCacheSize;

    /**
     * Lock timeout in milliseconds
     *
     * @var int
     */
    public readonly int $lockTimeoutMs;

    /**
     * Sleep before update lock in milliseconds
     *
     * @var int
     */
    public readonly int $sleepBeforeUpdateLock;

    /**
     * Number of event to keep during process before persisting
     *
     * @var int
     */
    public readonly int $persistBlockSize;

    /**
     * Update lock Threshold
     *
     * @var int
     */
    public readonly int $updateLockThreshold;

    /**
     * Number of retries in milliseconds to fill a gap detected
     *
     * @var array
     */
    public readonly array $retriesMs;

    /**
     * Detection windows as a string interval
     *
     * @var string|null
     */
    public readonly string|null $detectionWindows;

    public function getDispatchSignal(): bool
    {
        return $this->dispatchSignal;
    }

    public function getStreamCacheSize(): int
    {
        return $this->streamCacheSize;
    }

    public function getPersistBlockSize(): int
    {
        return $this->persistBlockSize;
    }

    public function getLockTimeoutMs(): int
    {
        return $this->lockTimeoutMs;
    }

    public function getSleepBeforeUpdateLock(): int
    {
        return $this->sleepBeforeUpdateLock;
    }

    public function getUpdateLockThreshold(): int
    {
        return $this->updateLockThreshold;
    }

    public function getRetriesMs(): array
    {
        return $this->retriesMs;
    }

    public function getDetectionWindows(): ?string
    {
        return $this->detectionWindows;
    }

    public function jsonSerialize(): array
    {
        return [
            self::DISPATCH_SIGNAL => $this->getDispatchSignal(),
            self::STREAM_CACHE_SIZE => $this->getStreamCacheSize(),
            self::PERSIST_BLOCK_SIZE => $this->getPersistBlockSize(),
            self::LOCK_TIMEOUT_MS => $this->getLockTimeoutMs(),
            self::SLEEP_BEFORE_UPDATE_LOCK => $this->getSleepBeforeUpdateLock(),
            self::UPDATE_LOCK_THRESHOLD => $this->getUpdateLockThreshold(),
            self::RETRIES_MS => $this->getRetriesMs(),
            self::DETECTION_WINDOWS => $this->getDetectionWindows(),
        ];
    }

    /**
     * @param  array|string  $retriesMs
     * @return void
     */
    protected function setUpRetriesMs(array|string $retriesMs): void
    {
        if (is_array($retriesMs)) {
            $this->retriesMs = $retriesMs;
        } else {
            [$start, $end, $step] = explode(',', $retriesMs);

            $this->retriesMs = range((int) $start, (int) $end, (int) $step);
        }
    }
}
