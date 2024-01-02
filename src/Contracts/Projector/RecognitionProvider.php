<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Provider\Checkpoint\CheckpointDTO;
use Chronhub\Storm\Projector\Provider\Checkpoint\CheckpointId;
use Chronhub\Storm\Projector\Provider\Checkpoint\InMemoryCheckpointModel;
use Illuminate\Support\Collection;

interface RecognitionProvider
{
    /**
     * Insert a new checkpoint.
     */
    public function insert(CheckpointDTO $checkpoint): void;

    /**
     * Insert a batch of checkpoints.
     *
     * @param array<CheckpointDTO> $checkpoints
     */
    public function insertBatch(array $checkpoints): void;

    /**
     * Check if checkpoint exists.
     */
    public function exists(CheckpointId $checkpointId): bool;

    /**
     * Get the last checkpoint for each stream
     *
     * @return Collection<InMemoryCheckpointModel>
     */
    public function lastCheckpointByProjectionName(string $projectionName): Collection;

    /**
     * Get the last checkpoint for a projection and stream.
     */
    public function lastCheckpoint(string $projectionName, string $streamName): ?CheckpointModel;

    /**
     * Delete snapshot by projection name.
     */
    public function delete(string $projectionName): void;

    /**
     * Delete checkpoint by projection name and stream name.
     */
    public function deleteByNames(string $projectionName, string $streamName): void;

    /**
     * Delete checkpoint by projection name, stream name and position.
     */
    public function deleteById(CheckpointId $checkpointId): void;

    /**
     * Delete checkpoint where created at is lower than given datetime.
     */
    public function deleteByDateLowerThan(string $projectionName, string $datetime): void;

    /**
     * Delete all checkpoints.
     */
    public function deleteAll(): void;

    /**
     * Get all checkpoints.
     *
     * @return Collection<CheckpointModel>
     */
    public function all(): Collection;
}
