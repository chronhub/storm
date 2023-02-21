<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface ProjectorOption extends JsonSerializable
{
    public const DISPATCH_SIGNAL = 'dispatchSignal';

    public const STREAM_CACHE_SIZE = 'streamCacheSize';

    public const LOCK_TIMEOUT_MS = 'lockTimeoutMs';

    public const SLEEP_BEFORE_UPDATE_LOCK = 'sleepBeforeUpdateLock';

    public const UPDATE_LOCK_THRESHOLD = 'updateLockThreshold';

    public const PERSIST_BLOCK_SIZE = 'persistBlockSize';

    public const RETRIES_MS = 'retriesMs';

    public const DETECTION_WINDOWS = 'detectionWindows';

    public function getDispatchSignal(): bool;

    /**
     * @return positive-int
     */
    public function getStreamCacheSize(): int;

    /**
     * @return positive-int
     */
    public function getPersistBlockSize(): int;

    /**
     * @return int<0, max>
     */
    public function getLockTimeoutMs(): int;

    /**
     * @return int<0, max>
     */
    public function getSleepBeforeUpdateLock(): int;

    /**
     * @return int<0, max>
     */
    public function getUpdateLockThreshold(): int;

    /**
     * @return array{int, int<0, max>}
     */
    public function getRetriesMs(): array;

    /**
     * @return null|string as date interval duration
     *
     * @see https://www.php.net/manual/en/dateinterval.construct.php
     */
    public function getDetectionWindows(): ?string;
}
