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
     * @var int<0,max>
     */
    protected readonly int $sleep;

    protected readonly int $incrementSleep;

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
    protected readonly int $loads;

    protected readonly ?string $detectionWindows;

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

    public function getIncrementSleep(): int
    {
        return $this->incrementSleep;
    }

    public function getLockout(): int
    {
        return $this->lockout;
    }

    public function getRetries(): array
    {
        return $this->retries;
    }

    public function getLoads(): int
    {
        return $this->loads;
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
            self::INCREMENT_SLEEP => $this->getIncrementSleep(),
            self::LOCKOUT => $this->getLockout(),
            self::RETRIES => $this->getRetries(),
            self::DETECTION_WINDOWS => $this->getDetectionWindows(),
            self::LOADS => $this->getLoads(),
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
