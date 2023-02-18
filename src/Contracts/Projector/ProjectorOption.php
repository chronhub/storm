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

    public function getStreamCacheSize(): int;

    public function getPersistBlockSize(): int;

    public function getLockTimeoutMs(): int;

    public function getSleepBeforeUpdateLock(): int;

    public function getUpdateLockThreshold(): int;

    /**
     * @return array{int}
     */
    public function getRetriesMs(): array;

    public function getDetectionWindows(): ?string;
}
