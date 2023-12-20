<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Stream\Checkpoint;
use JsonSerializable;

interface StreamManager extends JsonSerializable
{
    /**
     * Refresh event streams.
     *
     * Happens once when projector is started and at the end of each loop.
     * todo: could avoid refreshing event streams at the end of each loop,
     *  but it should be explicitly called by dev in factory to set only once discover
     */
    public function refreshStreams(array $eventStreams): void;

    /**
     * @param positive-int $position
     */
    public function insert(string $streamName, int $position): bool;

    /**
     * @param array<array{stream_name: string, position: positive-int, created_at: string, gaps: array<positive-int>}> $checkpoints
     */
    public function update(array $checkpoints): void;

    /**
     * Returns the current stream checkpoints.
     *
     * @return array<string,Checkpoint>
     */
    public function checkpoints(): array;

    /**
     * Resets stream positions.
     */
    public function resets(): void;
}
