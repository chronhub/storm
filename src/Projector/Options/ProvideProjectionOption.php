<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Options;

use function range;
use function explode;
use function is_array;

trait ProvideProjectionOption
{
    /**
     * Dispatch async signal
     */
    public readonly bool $signal;

    /**
     * Number of stream to keep in cache
     */
    public readonly int $cacheSize;

    /**
     * Lock timeout in milliseconds
     */
    public readonly int $timeout;

    /**
     * Sleep before update lock in milliseconds
     */
    public readonly int $sleep;

    /**
     * threshold of event to keep in memory before persisting
     */
    public readonly int $blockSize;

    /**
     * Update lock Threshold
     */
    public readonly int $lockout;

    /**
     * Number of retries in milliseconds to fill a gap detected
     */
    public readonly array $retries;

    /**
     * Detection windows as a string interval
     */
    public readonly string|null $detectionWindows;

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

    public function getSleep(): int
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

    public function getDetectionWindows(): ?string
    {
        return $this->detectionWindows;
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
