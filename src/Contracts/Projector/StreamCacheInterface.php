<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use JsonSerializable;

/**
 * @property-read int<1,max> $cacheSize
 */
interface StreamCacheInterface extends JsonSerializable
{
    /**
     * Add or replace stream name at the current position in the circular buffer.
     *
     * @param non-empty-string $streamName
     *
     * @throws InvalidArgumentException When stream name is already in buffer.
     */
    public function push(string $streamName): void;

    /**
     * Check if the stream name is in buffer.
     *
     * @param non-empty-string $streamName
     */
    public function has(string $streamName): bool;
}
