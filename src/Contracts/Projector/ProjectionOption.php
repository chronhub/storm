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
     * @return array{int, int<0, max>}|array
     */
    public function getRetries(): array;

    /**
     * @return null|string as date interval duration
     *
     * @see https://www.php.net/manual/en/dateinterval.construct.php
     */
    public function getDetectionWindows(): ?string;
}
