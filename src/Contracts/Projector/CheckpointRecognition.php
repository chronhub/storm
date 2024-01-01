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
     * unless projection is stopped or optional "only once discovery" is enabled
     */
    public function refreshStreams(array $eventStreams): void;

    /**
     * @param  positive-int $streamPosition
     * @return Checkpoint   the last checkpoint inserted with or without gap
     */
    public function insert(string $streamName, int $streamPosition): Checkpoint;

    /**
     * Update stream checkpoints.
     *
     * @param array<array{stream_name: string, position: int<0,max>, created_at: string, gaps: array<positive-int>}> $checkpoints
     */
    public function update(array $checkpoints): void;

    /**
     * Returns the last inserted stream checkpoints.
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
     * It also decrements the retries left.
     */
    public function sleepWhenGap(): void;

    /**
     * @return array{stream_name: string, position: int<0,max>, created_at: string, gaps: array<positive-int>}
     */
    public function jsonSerialize(): array;
}
