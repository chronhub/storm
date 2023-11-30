<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface ProjectionOption extends JsonSerializable
{
    /**
     * @var string
     */
    public const SIGNAL = 'signal';

    /**
     * @var string
     */
    public const CACHE_SIZE = 'cacheSize';

    /**
     * @var string
     */
    public const TIMEOUT = 'timeout';

    /**
     * @var string
     */
    public const SLEEP = 'sleep';

    /**
     * @var string
     */
    public const LOCKOUT = 'lockout';

    /**
     * @var string
     */
    public const BLOCK_SIZE = 'blockSize';

    /**
     * @var string
     */
    public const RETRIES = 'retries';

    /**
     * @var string
     */
    public const DETECTION_WINDOWS = 'detectionWindows';

    /**
     * Dispatch async signal
     */
    public function getSignal(): bool;

    /**
     * Get the number of stream to keep in cache
     * only apply for emitter projection
     *
     * @return positive-int
     */
    public function getCacheSize(): int;

    /**
     * Get threshold of events to keep in memory before persisting
     *
     * @return positive-int
     */
    public function getBlockSize(): int;

    /**
     * Get lock timeout in milliseconds
     *
     * @return int<1,max>
     */
    public function getTimeout(): int;

    /**
     * Get lock Threshold in milliseconds
     *
     * @return int<0,max>
     */
    public function getLockout(): int;

    /**
     * Get sleep in milliseconds before update lock
     *
     * @return int<0,max>
     */
    public function getSleep(): int;

    /**
     * Get retries in milliseconds when a gap detected
     *
     * @return array<int<0,max>>
     */
    public function getRetries(): array;

    /**
     * Get detection windows
     *
     * @return null|string as date interval duration
     */
    public function getDetectionWindows(): ?string;
}
