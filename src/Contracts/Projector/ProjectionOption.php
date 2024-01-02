<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Chronhub\Storm\Projector\Workflow\HaltOn;
use Chronhub\Storm\Projector\Workflow\Watcher\BatchStreamWatcher;
use Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher;
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
     * @var string
     */
    public const LOAD_LIMITER = 'loadLimiter';

    public const ONLY_ONCE_DISCOVERY = 'onlyOnceDiscovery';

    /**
     * Dispatch async signal
     */
    public function getSignal(): bool;

    /**
     * Get the number of streams to keep in cache
     * Available for emitter projection
     *
     * @return positive-int
     */
    public function getCacheSize(): int;

    /**
     * Get the threshold of events to keep in memory before persisting
     * Available for persistent projection
     *
     * @return positive-int
     */
    public function getBlockSize(): int;

    /**
     * Get lock timeout in milliseconds
     * Available for persistent projection
     *
     * @return positive-int
     */
    public function getTimeout(): int;

    /**
     * Get the lock threshold in milliseconds
     * Available for persistent projection
     *
     * @return int<0,max>
     */
    public function getLockout(): int;

    /**
     * Get sleep times
     *
     * @return array{int|float, int|float}
     *
     * @see BatchStreamWatcher
     * @see ConsumeWithSleepToken
     *
     * @example [1, 2] fixed sleep time of 0.5 second on each query
     * @example [5, 2.5] increment sleep times of a total of 6 seconds for five queries
     */
    public function getSleep(): array;

    /**
     * Get retries in milliseconds when a gap detected
     * Available for persistent projection
     *
     * By now, two retries are mandatory,
     * as the last retry could be considered as an UnrecoverableGap
     * when halt is set to stop projection on an unrecoverable gap.
     *
     * @see StopWatcher
     * @see HaltOn
     *
     * @return array<int<0,max>>
     */
    public function getRetries(): array;

    /**
     * Get detection windows
     *
     * @deprecated still need to set a replacement with checkpoints
     *
     * @return null|string as date interval duration
     */
    public function getDetectionWindows(): ?string;

    /**
     * Get loads limiter for the query filter
     *
     * Zero means no limit
     *
     * @return int<0,max>
     *
     * @see LoadLimiterProjectionQueryFilter
     */
    public function getLoadLimiter(): int;

    /**
     * Get "only once discovery"
     * Available for persistent projection
     */
    public function getOnlyOnceDiscovery(): bool;
}
