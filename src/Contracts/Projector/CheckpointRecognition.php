<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Stream\Checkpoint;
use JsonSerializable;

interface CheckpointRecognition extends JsonSerializable
{
    /**
     * Refresh event streams.
     *
     * Happens once when projector is started and at the end of each loop,
     * unless projection is stopped or only once discovery is enabled
     */
    public function refreshStreams(array $eventStreams): void;

    /**
     * @param  positive-int $position
     * @return Checkpoint   the last checkpoint inserted with or without gap
     */
    public function insert(string $streamName, int $position): Checkpoint;

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

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Sleep when a gap is detected.
     */
    public function sleepWhenGap(): void;
}
