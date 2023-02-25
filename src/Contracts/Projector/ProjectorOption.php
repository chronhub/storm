<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface ProjectorOption extends JsonSerializable
{
    public const SIGNAL = 'signal';

    public const CACHE_SIZE = 'cacheSize';

    public const TIMEOUT = 'timeout';

    public const SLEEP = 'sleep';

    public const LOCKOUT = 'lockout';

    public const BLOCK_SIZE = 'blockSize';

    public const RETRIES = 'retries';

    public const DETECTION_WINDOWS = 'detectionWindows';

    public function getSignal(): bool;

    /**
     * @return positive-int
     */
    public function getCacheSize(): int;

    /**
     * @return positive-int
     */
    public function getBlockSize(): int;

    /**
     * @return int<0, max>
     */
    public function getTimeout(): int;

    /**
     * @return int<0, max>
     */
    public function getSleep(): int;

    /**
     * @return int<0, max>
     */
    public function getLockout(): int;

    /**
     * @return array{int, int<0, max>}
     */
    public function getRetries(): array;

    /**
     * @return null|string as date interval duration
     *
     * @see https://www.php.net/manual/en/dateinterval.construct.php
     */
    public function getDetectionWindows(): ?string;
}
