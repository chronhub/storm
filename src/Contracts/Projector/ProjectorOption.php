<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface ProjectorOption extends JsonSerializable
{
    public const DISPATCH_SIGNAL = 'dispatch_signal';

    public const STREAM_CACHE_SIZE = 'stream_cache_size';

    public const LOCK_TIMEOUT_MS = 'lock_timeout_ms';

    public const SLEEP_BEFORE_UPDATE_LOCK = 'sleep_before_update_lock';

    public const UPDATE_LOCK_THRESHOLD = 'update_lock_threshold';

    public const PERSIST_BLOCK_SIZE = 'persist_block_size';

    public const RETRIES_MS = 'retries_ms';

    public const DETECTION_WINDOWS = 'detection_windows';

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
