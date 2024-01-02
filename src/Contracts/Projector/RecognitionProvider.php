<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Provider\Checkpoint\CheckpointDTO;
use Chronhub\Storm\Projector\Provider\Checkpoint\InMemoryCheckpointModel;
use Illuminate\Support\Collection;

interface RecognitionProvider
{
    public function insert(CheckpointDTO $checkpoint): void;

    /**
     * @param array<CheckpointDTO> $checkpoints
     */
    public function insertBatch(array $checkpoints): void;

    /**
     * Get the last checkpoint for each stream
     *
     * @return Collection<InMemoryCheckpointModel>
     */
    public function lastCheckpointByProjectionName(string $projectionName): Collection;

    /**
     * Get the last checkpoint for a projection and stream
     */
    public function lastCheckpoint(string $projectionName, string $streamName): ?InMemoryCheckpointModel;

    /**
     * Delete checkpoint by projection name
     */
    public function delete(string $projectionName): void;

    /**
     * Delete checkpoint by projection name and stream name
     */
    public function deleteByNames(string $projectionName, string $streamName): void;

    /**
     * Delete checkpoint by projection name, stream name and position
     */
    public function deleteById(string $projectionName, string $streamName, int $position): void;

    /**
     * Delete checkpoint where created at is lower than given datetime
     */
    public function deleteByDateLowerThan(string $projectionName, string $datetime): void;

    public function deleteAll(): void;

    public function all(): Collection;
}
